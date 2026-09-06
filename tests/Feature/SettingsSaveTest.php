<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\Admin\EnvService;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('renders the settings page for an admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.settings.index'))
        ->assertOk();
});

it('saves settings with the second currency enabled', function () {
    $payload = [
        'currency_code' => 'USD',
        'currency_symbol' => '$',
        'currency_position' => 'before',
        'currency_secondary_enabled' => '1',
        'currency_secondary_code' => 'KHR',
        'currency_secondary_symbol' => '៛',
        'currency_secondary_position' => 'after',
        'currency_secondary_rate' => '4100',
        'currency_secondary_decimals' => '0',
    ];

    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), $payload)
        ->assertRedirect(route('admin.settings.index'));

    expect(Setting::get('currency_secondary_rate'))->toBe('4100');
});

it('returns a JSON success for an AJAX save (no redirect)', function () {
    $this->actingAs($this->admin)
        ->putJson(route('admin.settings.update'), ['currency_code' => 'USD'])
        ->assertOk()
        ->assertJson(['success' => true]);
});

it('returns 422 JSON validation errors for an AJAX save', function () {
    $this->actingAs($this->admin)
        ->putJson(route('admin.settings.update'), [
            'social_links' => [['icon' => '', 'title' => 'Bad', 'url' => 'not-a-valid-url']],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('social_links.0.url');
});

it('saves the Facebook page token and keeps it when the field is left blank', function () {
    $envPath = storage_path('framework/testing-env-'.uniqid().'.env');
    file_put_contents($envPath, "APP_NAME=Shop\n");
    app()->loadEnvironmentFrom(basename($envPath));
    app()->useEnvironmentPath(dirname($envPath));

    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), [
            'currency_code' => 'USD',
            'facebook_publish_enabled' => '1',
            'facebook_page_id' => '123456',
            'facebook_page_access_token' => 'first-token',
        ])
        ->assertRedirect(route('admin.settings.index'));

    expect(app(EnvService::class)->get('FACEBOOK_PAGE_ACCESS_TOKEN'))->toBe('first-token')
        ->and(Setting::get('facebook_page_id'))->toBe('123456');

    // A blank password field on a later save must keep the stored token.
    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), [
            'currency_code' => 'USD',
            'facebook_publish_enabled' => '1',
            'facebook_page_id' => '123456',
            'facebook_page_access_token' => '',
        ])
        ->assertRedirect(route('admin.settings.index'));

    expect(app(EnvService::class)->get('FACEBOOK_PAGE_ACCESS_TOKEN'))->toBe('first-token');

    @unlink($envPath);
});
