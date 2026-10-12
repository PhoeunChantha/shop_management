<?php

declare(strict_types=1);

namespace App\Services\Frontend;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\CutLuyException;
use App\Models\CutluyPayment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WalletTopup;
use App\Services\Admin\LoyaltyService;
use App\Services\Admin\SettingService;
use App\Services\Admin\WalletService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CutLuy QR payments (Cambodia, USD): create a payment for an order or a
 * wallet top-up, verify webhooks, and settle what was paid.
 *
 * Money only moves on status `paid`. `scanned` means the customer opened the
 * QR in their banking app and has NOT paid yet — nothing is fulfilled on it.
 */
final class CutLuyService
{
    public const CURRENCY = 'USD';

    /** Reject webhook deliveries signed longer ago than this (replay guard). */
    private const SIGNATURE_TOLERANCE = 300;

    public function __construct(
        private readonly SettingService $settings,
        private readonly LoyaltyService $loyalty,
        private readonly WalletService $wallet,
    ) {}

    public function configured(): bool
    {
        return filled($this->settings->cutluy()['api_key']);
    }

    /**
     * The open (or already paid) QR for this order / top-up, creating one at
     * CutLuy when there is none. Reloading the pay page never creates a second
     * payment, so the customer cannot be charged twice and quota is not wasted.
     *
     * @throws CutLuyException
     */
    public function paymentFor(Order|WalletTopup $payable): CutluyPayment
    {
        $existing = $this->latestFor($payable);

        if ($existing && ($existing->isOpen() || $existing->isPaid())) {
            return $existing;
        }

        return $this->create($payable);
    }

    public function latestFor(Order|WalletTopup $payable): ?CutluyPayment
    {
        return CutluyPayment::query()
            ->where('payable_type', $payable->getMorphClass())
            ->where('payable_id', $payable->getKey())
            ->latest('id')
            ->first();
    }

    /**
     * @throws CutLuyException
     */
    private function create(Order|WalletTopup $payable): CutluyPayment
    {
        $reference = (string) ($payable instanceof Order ? $payable->order_number : $payable->tran_id);
        // Always the server-side total — never an amount sent by the browser.
        $amount = round((float) ($payable instanceof Order ? $payable->grand_total : $payable->amount), 2);

        // A new attempt (after an expired / failed QR) needs its own key, or
        // CutLuy would hand back the dead payment again.
        $attempt = CutluyPayment::query()
            ->where('payable_type', $payable->getMorphClass())
            ->where('payable_id', $payable->getKey())
            ->count() + 1;
        $key = $attempt === 1 ? $reference : $reference.'-'.$attempt;

        $data = $this->send('post', '/v1/payments', [
            'amount' => $amount,
            'reference_id' => $reference,
            'metadata' => ['type' => $payable instanceof Order ? 'order' : 'wallet_topup', 'id' => $payable->getKey()],
            'idempotency_key' => $key,
        ]);

        if (blank($data['id'] ?? null)) {
            throw new CutLuyException('invalid_response', 'CutLuy returned no payment id.');
        }

        $payment = CutluyPayment::updateOrCreate(
            ['payment_id' => (string) $data['id']],
            [
                'payable_type' => $payable->getMorphClass(),
                'payable_id' => $payable->getKey(),
                'reference_id' => $reference,
                'idempotency_key' => $key,
                'amount' => $amount,
                'currency' => self::CURRENCY,
                'status' => (string) ($data['status'] ?? 'pending'),
                'checkout_url' => $data['checkout_url'] ?? null,
                'qr_string' => $data['qr_string'] ?? null,
                'expires_at' => $this->timestamp($data['expires_at'] ?? null),
                'payload' => $data,
            ],
        );

        if ($payable instanceof Order) {
            // Mirror into the shared payments table so Admin → Payments lists it.
            Payment::updateOrCreate(
                ['order_id' => $payable->id, 'tran_id' => $payment->payment_id],
                ['gateway' => 'cutluy', 'payment_option' => 'khqr', 'amount' => $amount, 'currency' => self::CURRENCY, 'status' => 'pending'],
            );
        }

        return $payment;
    }

    /**
     * Re-read the payment from CutLuy and apply whatever it says. CutLuy's API
     * — not the webhook body — is the source of truth for what was paid.
     *
     * @throws CutLuyException
     */
    public function sync(CutluyPayment $payment): CutluyPayment
    {
        return $this->apply($payment, $this->send('get', '/v1/payments/'.rawurlencode($payment->payment_id)));
    }

    /**
     * Apply a payment object from CutLuy: update the status and, when it is
     * `paid` for the right amount, settle the order / top-up. Row-locked and
     * idempotent — a duplicate webhook or a poll racing it settles once.
     *
     * @param  array<string, mixed>  $data
     */
    public function apply(CutluyPayment $payment, array $data): CutluyPayment
    {
        return DB::transaction(function () use ($payment, $data): CutluyPayment {
            /** @var CutluyPayment $locked */
            $locked = CutluyPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->isPaid()) {
                return $locked;
            }

            $status = (string) ($data['status'] ?? $locked->status);

            if ($status === 'paid' && ! $this->amountMatches($locked, $data)) {
                Log::critical('CutLuy payment amount mismatch — not fulfilled.', [
                    'payment_id' => $locked->payment_id,
                    'expected' => $locked->amount.' '.$locked->currency,
                    'received' => ($data['amount'] ?? '?').' '.($data['currency'] ?? '?'),
                ]);
                $status = 'failed';
            }

            $locked->update([
                'status' => $status,
                'payload' => $data,
                'paid_at' => $status === 'paid' ? ($this->timestamp($data['approved_at'] ?? null) ?? now()) : null,
            ]);

            if ($status === 'paid') {
                $this->settle($locked);
            } elseif (in_array($status, ['expired', 'failed'], true)) {
                Payment::where('gateway', 'cutluy')->where('tran_id', $locked->payment_id)->update(['status' => 'failed']);
            }

            return $locked;
        });
    }

    /**
     * Verify a webhook: hex HMAC-SHA256 of "{t}.{rawBody}" with the endpoint
     * secret, compared in constant time, and not older than five minutes.
     * `$rawBody` must be the untouched request body.
     */
    public function verifySignature(string $rawBody, ?string $header): bool
    {
        $secret = $this->settings->cutluy()['webhook_secret'];

        if (blank($secret) || blank($header)) {
            return false;
        }

        $parts = [];
        foreach (explode(',', (string) $header) as $pair) {
            [$name, $value] = array_pad(explode('=', trim($pair), 2), 2, '');
            $parts[$name] = $value;
        }

        $timestamp = $parts['t'] ?? '';
        $signature = $parts['v1'] ?? '';

        if (! ctype_digit($timestamp) || $signature === '') {
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::SIGNATURE_TOLERANCE) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret), $signature);
    }

    /**
     * Remaining quota for the account (plan, limit, used, remaining, period).
     *
     * @return array<string, mixed>
     *
     * @throws CutLuyException
     */
    public function usage(): array
    {
        return $this->send('get', '/v1/usage');
    }

    private function settle(CutluyPayment $payment): void
    {
        $payable = $payment->payable;

        if ($payable instanceof Order) {
            $this->settleOrder($payable, $payment);
        } elseif ($payable instanceof WalletTopup) {
            $this->settleTopup($payable);
        }
    }

    private function settleOrder(Order $order, CutluyPayment $payment): void
    {
        Payment::where('gateway', 'cutluy')->where('tran_id', $payment->payment_id)
            ->update(['status' => 'completed']);

        if ($order->payment_status === PaymentStatus::Paid) {
            return;
        }

        $order->forceFill([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => $order->paid_at ?? now(),
            'payment_reference' => $payment->payment_id,
        ])->save();

        $order->logEvent('payment', 'Paid by CutLuy QR', 'Payment '.$payment->payment_id);

        // Paid after the unpaid-order expiry cancelled it (stock already
        // returned): flag it so an admin re-opens or refunds it.
        if ($order->status === OrderStatus::Cancelled) {
            $order->logEvent('payment', 'Payment received after the order was cancelled', 'Review this order: re-open it (check stock) or refund the customer.');
        }

        $this->loyalty->awardForOrder($order);
    }

    private function settleTopup(WalletTopup $topup): void
    {
        /** @var WalletTopup|null $locked */
        $locked = WalletTopup::whereKey($topup->id)->lockForUpdate()->first();

        if (! $locked || $locked->status === 'completed') {
            return;
        }

        $locked->update(['status' => 'completed', 'reviewed_at' => now()]);
        $this->wallet->credit($locked->user, (float) $locked->amount, 'topup', 'Wallet top-up '.$locked->tran_id);
    }

    private function timestamp(mixed $value): ?Carbon
    {
        try {
            return filled($value) ? Carbon::parse((string) $value) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function amountMatches(CutluyPayment $payment, array $data): bool
    {
        $cents = fn (mixed $amount): int => (int) round(((float) $amount) * 100);

        return strtoupper((string) ($data['currency'] ?? self::CURRENCY)) === $payment->currency
            && $cents($data['amount'] ?? 0) === $cents($payment->amount);
    }

    /**
     * One request to CutLuy. Never retries on its own: a 429 is surfaced with
     * its Retry-After so the caller waits instead of looping.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     *
     * @throws CutLuyException
     */
    private function send(string $method, string $path, array $body = []): array
    {
        $config = $this->settings->cutluy();

        if (blank($config['api_key'])) {
            throw new CutLuyException('not_configured', 'CutLuy API key is not set.');
        }

        try {
            $response = $method === 'post'
                ? $this->client($config)->post($path, $body)
                : $this->client($config)->get($path);
        } catch (ConnectionException $e) {
            throw new CutLuyException('unreachable', 'Could not reach CutLuy: '.$e->getMessage());
        }

        if ($response->successful()) {
            return (array) $response->json();
        }

        $code = (string) ($response->json('error') ?: match ($response->status()) {
            401 => 'unauthorized',
            402 => 'quota_exceeded',
            403 => 'account_suspended',
            429 => 'rate_limited',
            default => 'http_'.$response->status(),
        });

        $exception = new CutLuyException(
            $code,
            (string) ($response->json('message') ?: 'CutLuy request failed.'),
            $response->status(),
            $response->status() === 429 ? max(1, (int) $response->header('Retry-After')) : null,
        );

        if ($exception->needsOwnerAction()) {
            Log::critical('CutLuy needs attention: '.$code, ['status' => $response->status(), 'message' => $exception->getMessage()]);
        } else {
            Log::warning('CutLuy request failed: '.$code, ['status' => $response->status(), 'path' => $path]);
        }

        throw $exception;
    }

    /**
     * @param  array{api_key: string, webhook_secret: string, base_url: string}  $config
     */
    private function client(array $config): PendingRequest
    {
        return Http::baseUrl($config['base_url'])
            ->withToken($config['api_key'])
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15);
    }
}
