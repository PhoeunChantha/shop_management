<?php

use App\Models\Setting;
use App\Services\Admin\SettingService;

it('formats money with the default USD currency', function () {
    expect(money(1250))->toBe('$1,250.00')
        ->and(app(SettingService::class)->formatMoney(9.5))->toBe('$9.50');
});

it('honours a configured symbol and after-position', function () {
    Setting::set('currency_symbol', '៛', 'localization');
    Setting::set('currency_position', 'after', 'localization');

    expect(money(1200))->toBe('1,200.00៛');
});

it('adds baseline security headers to storefront responses', function () {
    $this->get(route('frontend.home'))
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});
