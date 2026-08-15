<?php

use App\Models\Setting;
use App\Services\Admin\SettingService;

it('shows only the base currency when no secondary is configured', function () {
    expect(app(SettingService::class)->displayCurrencies())->toHaveCount(1);
    expect(dprice(10))->toBe(money(10)); // no conversion
});

it('converts browsing prices to the selected secondary currency', function () {
    Setting::set('currency_secondary_enabled', '1', 'localization');
    Setting::set('currency_secondary_code', 'KHR', 'localization');
    Setting::set('currency_secondary_symbol', '៛', 'localization');
    Setting::set('currency_secondary_position', 'after', 'localization');
    Setting::set('currency_secondary_rate', '4000', 'localization');
    Setting::set('currency_secondary_decimals', '0', 'localization');

    $svc = app(SettingService::class);
    expect($svc->displayCurrencies())->toHaveCount(2);

    // Default (no session) stays base.
    expect(dprice(10))->toBe(money(10));

    // Select KHR → browsing prices convert; base money() is untouched.
    session(['display_currency' => 'KHR']);
    expect(dprice(10))->toBe('40,000៛');
    expect(money(10))->toBe('$10.00'); // source of truth unchanged
});

it('ignores an unknown display currency and falls back to base', function () {
    session(['display_currency' => 'XYZ']);
    expect(dprice(10))->toBe(money(10));
});
