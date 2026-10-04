<?php

declare(strict_types=1);

namespace App\Services\Frontend;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Admin\RecaptchaService;
use App\Services\Admin\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Anti-fraud gate in front of order placement: guest checkout switch,
 * honeypot, reCAPTCHA and a cap on open unpaid orders per email / IP.
 * Settings live in Admin → Settings → Checkout & Security (+ reCAPTCHA).
 */
final class CheckoutGuardService
{
    /** Hidden form field real shoppers never fill in. */
    public const HONEYPOT = 'company_website';

    public function __construct(
        private readonly SettingService $settings,
        private readonly RecaptchaService $recaptcha,
    ) {}

    /** True when guest checkout is switched off and the visitor is a guest. */
    public function requiresLogin(): bool
    {
        return ! Auth::check() && ! $this->settings->checkoutSecurity()['guest_checkout'];
    }

    public function recaptchaSiteKey(): ?string
    {
        return $this->recaptcha->protectsCheckout() ? $this->recaptcha->siteKey() : null;
    }

    /**
     * Returns a customer-facing reason to refuse the order, or null when it
     * may be placed.
     */
    public function check(Request $request, string $email): ?string
    {
        // Bots fill every field; people never see this one.
        if (filled($request->input(self::HONEYPOT))) {
            return __('We could not place your order. Please try again.');
        }

        if ($this->recaptcha->protectsCheckout()
            && ! $this->recaptcha->verify((string) $request->input('g-recaptcha-response', ''), 'checkout')) {
            return __('Security check failed. Please try again.');
        }

        $max = $this->settings->checkoutSecurity()['max_unpaid_orders'];

        if ($max > 0 && $this->openUnpaidOrders($email, (string) $request->ip()) >= $max) {
            return __('You already have :max unpaid orders. Please complete payment for them (or contact us) before placing a new one.', ['max' => $max]);
        }

        return null;
    }

    /**
     * Pending, unpaid orders placed with this email or from this IP.
     */
    public function openUnpaidOrders(string $email, string $ip): int
    {
        return Order::query()
            ->where('status', OrderStatus::Pending->value)
            ->where('payment_status', PaymentStatus::Unpaid->value)
            ->where(function ($query) use ($email, $ip): void {
                $query->whereRaw('LOWER(customer_email) = ?', [mb_strtolower(trim($email))]);

                if ($ip !== '') {
                    $query->orWhere('ip_address', $ip);
                }
            })
            ->count();
    }
}
