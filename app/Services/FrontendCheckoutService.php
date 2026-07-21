<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ImageManager;
use App\Models\ShippingMethod;
use App\Models\TaxRule;

/**
 * Supplies the storefront checkout with admin-managed shipping methods, payment
 * methods and tax so delivery options + totals reflect real configuration.
 */
final class FrontendCheckoutService
{
    public function __construct(private readonly SettingService $settings) {}

    /**
     * Active shipping methods mapped for the checkout (+ client totals JS).
     *
     * @return array<int, array<string, mixed>>
     */
    public function shippingMethods(): array
    {
        return ShippingMethod::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ShippingMethod $m): array => [
                'id' => $m->id,
                'name' => $m->name,
                'description' => $m->delivery_time ?: ($m->description ?: ''),
                'type' => $m->type->value,
                'rate' => (float) $m->rate,
                'free_over' => $m->free_over_amount !== null ? (float) $m->free_over_amount : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Active payment methods from Settings (online + manual), sorted.
     *
     * @return array<int, array<string, mixed>>
     */
    public function paymentMethods(): array
    {
        return collect($this->settings->paymentMethods())
            ->filter(fn (array $p): bool => (bool) ($p['status'] ?? false))
            ->sortBy('sort_order')
            ->map(fn (array $p): array => [
                'code' => $p['code'] ?? $p['id'] ?? 'card',
                'name' => $p['name'] ?? 'Card',
                'type' => $p['type'] ?? 'online',
                'description' => $p['description'] ?? '',
                'instructions' => $p['instructions'] ?? '',
                'qr_image' => ! empty($p['qr_image']) ? ImageManager::url($p['qr_image'], 'settings') : null,
                'bank_name' => $p['bank_name'] ?? '',
                'account_name' => $p['account_name'] ?? '',
                'account_number' => $p['account_number'] ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * Effective tax rate (fraction, e.g. 0.085) from the applicable active,
     * exclusive tax rule (lowest sort order). Inclusive rules are already in
     * the price, so they are not added at checkout.
     */
    public function taxRate(): float
    {
        $percent = (float) TaxRule::query()
            ->where('status', true)
            ->where('is_inclusive', false)
            ->orderBy('sort_order')
            ->value('rate');

        return round($percent / 100, 4);
    }
}
