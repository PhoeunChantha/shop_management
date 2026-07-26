<?php

use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use App\Services\Admin\SettingService;

it('captures a newsletter subscriber (lowercased)', function () {
    $this->post(route('frontend.newsletter.subscribe'), ['email' => 'Fan@Example.com'])
        ->assertRedirect();

    expect(NewsletterSubscriber::where('email', 'fan@example.com')->exists())->toBeTrue();
});

it('does not duplicate an existing subscriber', function () {
    NewsletterSubscriber::create(['email' => 'fan@example.com', 'subscribed_at' => now()]);

    $this->post(route('frontend.newsletter.subscribe'), ['email' => 'fan@example.com'])
        ->assertRedirect();

    expect(NewsletterSubscriber::where('email', 'fan@example.com')->count())->toBe(1);
});

it('validates the email', function () {
    $this->post(route('frontend.newsletter.subscribe'), ['email' => 'not-an-email'])
        ->assertInvalid('email');

    expect(NewsletterSubscriber::count())->toBe(0);
});

it('reads newsletter copy from settings with a fallback', function () {
    expect(app(SettingService::class)->newsletter()['title'])->toBe('Get 10% off your first order');

    Setting::set('newsletter_title', 'Join the club', 'home');

    expect(app(SettingService::class)->newsletter()['title'])->toBe('Join the club');
});
