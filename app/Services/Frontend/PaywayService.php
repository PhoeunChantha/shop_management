<?php

declare(strict_types=1);

namespace App\Services\Frontend;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\WalletTopup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * ABA PayWay (Purchase) integration.
 *
 * Flow: build an auto-submitting form to PayWay's hosted checkout, the customer
 * pays (card / ABA PAY / KHQR), PayWay pushes back to our callback, and we
 * confirm the real status via the transaction-detail API before marking paid.
 *
 * NOTE: the `hash` field ORDER below follows ABA's standard PHP sample. If your
 * PayWay integration guide lists a different field order, adjust HASH_FIELDS to
 * match — that is the single source of truth for the signature.
 */
final class PaywayService
{
    /**
     * Fields concatenated (in this exact order) to build the purchase HMAC.
     */
    private const HASH_FIELDS = [
        'req_time', 'merchant_id', 'tran_id', 'amount', 'items', 'shipping',
        'ctid', 'pwt', 'firstname', 'lastname', 'email', 'phone', 'type',
        'payment_option', 'return_url', 'cancel_url', 'continue_success_url',
        'return_deeplink', 'currency', 'custom_fields', 'return_params',
    ];

    public function configured(): bool
    {
        return filled($this->merchantId()) && filled($this->apiKey());
    }

    /**
     * Build the endpoint + signed form fields for a PayWay purchase, and record
     * a pending Payment.
     *
     * @return array{url: string, fields: array<string, string>}
     */
    public function purchase(Order $order, string $paymentOption, string $returnUrl, string $successUrl, string $cancelUrl): array
    {
        $items = base64_encode((string) json_encode(
            $order->details->map(fn ($d): array => [
                'name' => (string) $d->name,
                'quantity' => (int) $d->quantity,
                'price' => (float) $d->price,
            ])->values()->all()
        ));

        [$first, $last] = $this->splitName($order->customer_name);

        $fields = [
            'req_time' => gmdate('YmdHis'),
            'merchant_id' => (string) $this->merchantId(),
            'tran_id' => (string) $order->order_number,
            'amount' => number_format((float) $order->grand_total, 2, '.', ''),
            'items' => $items,
            'shipping' => number_format((float) $order->shipping_total, 2, '.', ''),
            'ctid' => '',
            'pwt' => '',
            'firstname' => $first,
            'lastname' => $last,
            'email' => (string) ($order->customer_email ?? ''),
            'phone' => (string) ($order->customer_phone ?? ''),
            'type' => 'purchase',
            'payment_option' => $paymentOption,
            'return_url' => base64_encode($returnUrl),
            'cancel_url' => $cancelUrl,
            'continue_success_url' => $successUrl,
            'return_deeplink' => '',
            'currency' => $this->currency(),
            'custom_fields' => '',
            'return_params' => (string) $order->order_number,
        ];

        $fields['hash'] = $this->hash($fields);

        Payment::updateOrCreate(
            ['order_id' => $order->id, 'tran_id' => $order->order_number],
            [
                'gateway' => 'payway',
                'payment_option' => $paymentOption ?: null,
                'amount' => (float) $order->grand_total,
                'currency' => $this->currency(),
                'status' => 'pending',
            ],
        );

        return [
            'url' => rtrim((string) $this->baseUrl(), '/').'/api/payment-gateway/v1/payments/purchase',
            'fields' => $fields,
        ];
    }

    /**
     * Build the signed PayWay form for a wallet top-up (not tied to an order).
     *
     * @return array{url: string, fields: array<string, string>}
     */
    public function purchaseTopup(WalletTopup $topup, User $user, string $returnUrl, string $successUrl, string $cancelUrl): array
    {
        $amount = number_format((float) $topup->amount, 2, '.', '');
        $items = base64_encode((string) json_encode([
            ['name' => 'Wallet top-up', 'quantity' => 1, 'price' => (float) $topup->amount],
        ]));

        [$first, $last] = $this->splitName($user->name);

        $fields = [
            'req_time' => gmdate('YmdHis'),
            'merchant_id' => (string) $this->merchantId(),
            'tran_id' => (string) $topup->tran_id,
            'amount' => $amount,
            'items' => $items,
            'shipping' => '0.00',
            'ctid' => '',
            'pwt' => '',
            'firstname' => $first,
            'lastname' => $last,
            'email' => (string) $user->email,
            'phone' => '',
            'type' => 'purchase',
            'payment_option' => '',
            'return_url' => base64_encode($returnUrl),
            'cancel_url' => $cancelUrl,
            'continue_success_url' => $successUrl,
            'return_deeplink' => '',
            'currency' => $this->currency(),
            'custom_fields' => '',
            'return_params' => (string) $topup->tran_id,
        ];

        $fields['hash'] = $this->hash($fields);

        return [
            'url' => rtrim((string) $this->baseUrl(), '/').'/api/payment-gateway/v1/payments/purchase',
            'fields' => $fields,
        ];
    }

    /**
     * Ask PayWay for the authoritative status of a transaction.
     *
     * @return array{approved: bool, raw: array<string, mixed>}
     */
    public function verifyTransaction(string $tranId): array
    {
        $reqTime = gmdate('YmdHis');
        $b4hash = $reqTime.$this->merchantId().$tranId;

        $response = Http::asForm()
            ->timeout(20)
            ->post(rtrim((string) $this->baseUrl(), '/').'/api/payment-gateway/v1/payments/check-transaction-2', [
                'req_time' => $reqTime,
                'merchant_id' => (string) $this->merchantId(),
                'tran_id' => $tranId,
                'hash' => base64_encode(hash_hmac('sha512', $b4hash, (string) $this->apiKey(), true)),
            ]);

        $data = $response->json() ?: [];
        $paymentStatus = (string) data_get($data, 'data.payment_status', data_get($data, 'payment_status', ''));

        return [
            'approved' => strtoupper($paymentStatus) === 'APPROVED',
            'raw' => $data,
        ];
    }

    /**
     * Confirm an order's payment with PayWay and update the order + payment
     * record accordingly. Returns true when the payment is approved. Idempotent
     * — safe to call from both the pushback and the customer return.
     */
    public function confirm(Order $order): bool
    {
        $payment = Payment::where('order_id', $order->id)->latest('id')->first();

        if ($order->payment_status === PaymentStatus::Paid) {
            return true;
        }

        $result = $this->verifyTransaction((string) $order->order_number);

        if ($result['approved']) {
            $order->forceFill([
                'payment_status' => PaymentStatus::Paid,
                'paid_at' => $order->paid_at ?? now(),
            ])->save();

            $payment?->update(['status' => 'completed', 'meta' => $result['raw']]);

            return true;
        }

        $payment?->update(['status' => 'failed', 'meta' => $result['raw']]);

        return false;
    }

    /**
     * @param  array<string, string>  $fields
     */
    public function hash(array $fields): string
    {
        $b4hash = '';
        foreach (self::HASH_FIELDS as $key) {
            $b4hash .= $fields[$key] ?? '';
        }

        return base64_encode(hash_hmac('sha512', $b4hash, (string) $this->apiKey(), true));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(?string $name): array
    {
        $name = trim((string) $name);
        $first = Str::before($name, ' ');
        $last = trim(Str::after($name, ' '));

        return [$first ?: 'Customer', $last !== $first ? $last : ''];
    }

    private function baseUrl(): ?string
    {
        return config('services.payway.base_url');
    }

    private function merchantId(): ?string
    {
        return config('services.payway.merchant_id');
    }

    private function apiKey(): ?string
    {
        return config('services.payway.api_key');
    }

    private function currency(): string
    {
        return (string) config('services.payway.currency', 'USD');
    }
}
