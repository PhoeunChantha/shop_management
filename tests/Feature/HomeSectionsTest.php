<?php

use App\Models\Setting;
use App\Services\Admin\SettingService;

it('defaults every homepage rail to shown', function () {
    $sections = app(SettingService::class)->homeSections();

    expect($sections['best']['enabled'])->toBeTrue()
        ->and($sections['flash']['enabled'])->toBeTrue()
        ->and($sections['new']['enabled'])->toBeTrue()
        ->and($sections['trending']['enabled'])->toBeTrue()
        ->and($sections['best']['title'])->toBe('Best sellers');
});

it('reflects admin toggles and custom headings', function () {
    Setting::set('home_trending_enabled', '0', 'home');
    Setting::set('home_best_title', 'Fan favourites', 'home');

    $sections = app(SettingService::class)->homeSections();

    expect($sections['trending']['enabled'])->toBeFalse()
        ->and($sections['best']['title'])->toBe('Fan favourites');
});

it('renders the home page without error', function () {
    $this->get(route('frontend.home'))->assertOk();
});
