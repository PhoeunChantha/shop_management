<?php

use App\Helpers\ImageManager;
use App\Services\Admin\SettingService;

if (! function_exists('Imageurl')) {
    /**
     * Global shortcut to ImageManager::url() — usable bare in any Blade/PHP:
     *   {{ Imageurl($category->image, 'categories') }}
     */
    function Imageurl(?string $name, string $folder): ?string
    {
        return ImageManager::url($name, $folder);
    }
}

if (! function_exists('money')) {
    /**
     * Format a BASE-currency amount (source of truth). Used by admin, invoices,
     * emails and order records — never converts.
     *   {{ money($order->grand_total) }}  →  "$1,250.00"
     */
    function money(float|int|string|null $amount, int $decimals = 2): string
    {
        return app(SettingService::class)->formatMoney((float) $amount, $decimals);
    }
}

if (! function_exists('dprice')) {
    /**
     * Format a base amount into the shopper's selected DISPLAY currency
     * (storefront browsing prices; converts by the configured exchange rate).
     * The checkout still charges the base currency.
     */
    function dprice(float|int|string|null $amount): string
    {
        return app(SettingService::class)->formatDisplay((float) $amount);
    }
}
