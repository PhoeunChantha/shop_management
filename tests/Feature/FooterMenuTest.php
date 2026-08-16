<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\Admin\SettingService;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

it('renders admin-managed footer columns on the storefront', function () {
    Setting::set('footer_links', json_encode([
        ['column' => 'Support', 'label' => 'Help Center', 'url' => 'https://help.example.com'],
        ['column' => 'Support', 'label' => 'Returns', 'url' => '/returns'],
    ]), 'footer');

    $this->get(route('frontend.home'))
        ->assertOk()
        ->assertSee('Support', false)
        ->assertSee('Help Center', false)
        ->assertSee('https://help.example.com', false);
});

it('falls back to default footer columns when unconfigured', function () {
    $this->get(route('frontend.home'))
        ->assertOk()
        ->assertSee('Our Story', false); // from the default Brand column
});

it('groups footer links by column and drops incomplete rows', function () {
    Setting::set('footer_links', json_encode([
        ['column' => 'Help', 'label' => 'FAQ', 'url' => '/faq'],
        ['column' => '', 'label' => 'Orphan', 'url' => '/x'],   // no column → dropped
        ['column' => 'Help', 'label' => '', 'url' => '/y'],     // no label → dropped
        ['column' => 'Brand', 'label' => 'About', 'url' => '/about'],
    ]), 'footer');

    $cols = app(SettingService::class)->footerColumns();

    expect($cols)->toHaveKeys(['Help', 'Brand'])
        ->and($cols['Help'])->toHaveCount(1)
        ->and($cols['Help'][0]['label'])->toBe('FAQ');
});

it('persists footer links through the settings save, dropping blanks', function () {
    app(SettingService::class)->save([
        'footer_links' => [
            ['column' => 'Help', 'label' => 'FAQ', 'url' => '/faq'],
            ['column' => 'Help', 'label' => '', 'url' => '/nope'],
        ],
    ]);

    expect(app(SettingService::class)->footerLinks())->toHaveCount(1);
});

it('shows the Footer menu tab in admin settings', function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('Footer menu', false)
        ->assertSee('footer_links', false);
});
