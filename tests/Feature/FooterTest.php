<?php

use App\Models\Setting;

it('renders the footer from admin settings (name, tagline, social links)', function () {
    Setting::set('site_name', 'Acme Threads', 'general');
    Setting::set('site_tagline', 'Wear the vibe every day', 'general');
    Setting::set('social_links', json_encode([
        ['icon' => 'fa-brands fa-instagram', 'title' => 'Instagram', 'url' => 'https://instagram.com/acme'],
        ['icon' => 'fa-brands fa-tiktok', 'title' => 'TikTok', 'url' => 'https://tiktok.com/@acme'],
    ]), 'social');

    $this->get(route('frontend.home'))
        ->assertOk()
        ->assertSee('Acme Threads', false)
        ->assertSee('Wear the vibe every day', false)
        ->assertSee('https://instagram.com/acme', false)
        ->assertSee('fa-brands fa-instagram', false)
        ->assertSee('© '.date('Y').' Acme Threads', false);
});

it('falls back gracefully when no footer settings are configured', function () {
    $this->get(route('frontend.home'))
        ->assertOk()
        // Default tagline still shows; no social row required.
        ->assertSee('Premium heavyweight tees', false);
});

it('renders the admin-configured favicon on the storefront', function () {
    Setting::set('site_favicon', 'brand-favicon.png', 'general');

    $this->get(route('frontend.home'))
        ->assertOk()
        ->assertSee('<link rel="icon"', false)
        ->assertSee('brand-favicon.png', false);
});
