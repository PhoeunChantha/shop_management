<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('translates dropdown options in Khmer but keeps enum labels stable', function () {
    app()->setLocale('km');

    expect(OrderStatus::options()['delivered'])->toBe(__('Delivered'))->not->toBe('Delivered')
        ->and(PaymentStatus::options()['paid'])->not->toBe('Paid')
        // label() stays English: storefront code compares against it.
        ->and(OrderStatus::Delivered->label())->toBe('Delivered');
});

it('shows the orders filter and menu in Khmer', function () {
    $html = $this->actingAs($this->admin)->withSession(['locale' => 'km'])
        ->get(route('admin.orders.index'))->assertOk()->getContent();

    expect($html)->toContain(__('Apply Filters', [], 'km'))
        ->toContain(__('Wallets', [], 'km'))
        ->toContain(__('Top-up Requests', [], 'km'))
        ->toContain(__('Unfulfilled', [], 'km'))
        ->not->toContain('Apply Filters')
        ->toContain('lg:grid-cols-3');
});
