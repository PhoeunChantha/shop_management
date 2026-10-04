<?php

declare(strict_types=1);

namespace App\Services\Admin\Reports;

use App\Services\Admin\SettingService;
use Illuminate\Support\Str;

/**
 * Display names for the payment-method codes stored on orders. Methods are
 * configured in Settings (not an enum), so names are looked up there; a code
 * that is no longer configured still gets a readable fallback.
 */
final class PaymentMethodNames
{
    /** @var array<string, string>|null */
    private ?array $names = null;

    public function __construct(private readonly SettingService $settings) {}

    public function for(?string $code): string
    {
        if (blank($code)) {
            return __('Not recorded');
        }

        $this->names ??= collect($this->settings->paymentMethods())
            ->mapWithKeys(fn (array $method) => [(string) $method['code'] => (string) $method['name']])
            ->all();

        return $this->names[$code] ?? Str::headline($code);
    }

    /**
     * Code → name for filter dropdowns.
     *
     * @return array<string, string>
     */
    public function options(): array
    {
        return collect($this->settings->paymentMethods())
            ->mapWithKeys(fn (array $method) => [(string) $method['code'] => (string) $method['name']])
            ->all();
    }
}
