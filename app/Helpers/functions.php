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
     * Format an amount with the store's configured currency symbol/position:
     *   {{ money($order->grand_total) }}  →  "$1,250.00" (or "1 250,00 ₫", etc.)
     */
    function money(float|int|string|null $amount, int $decimals = 2): string
    {
        return app(SettingService::class)->formatMoney((float) $amount, $decimals);
    }
}
