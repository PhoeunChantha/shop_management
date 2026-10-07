<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $this->customer = User::factory()->create(['wallet_balance' => 40]);
    $this->customer->assignRole('customer');
});

it('lists customer wallets for an admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.wallets.index'))
        ->assertOk()
        ->assertSee($this->customer->email);
});

it('lets an admin credit a wallet', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.wallets.adjust', $this->customer), ['direction' => 'credit', 'amount' => 15, 'note' => 'Gift'])
        ->assertRedirect();

    expect((float) $this->customer->refresh()->wallet_balance)->toBe(55.0);
});

it('lets an admin debit a wallet but not below zero', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.wallets.adjust', $this->customer), ['direction' => 'debit', 'amount' => 1000])
        ->assertRedirect();

    // Debit rejected (insufficient) — balance unchanged.
    expect((float) $this->customer->refresh()->wallet_balance)->toBe(40.0);
});

it('forbids a customer from the admin wallets page', function () {
    $this->actingAs($this->customer)
        ->get(route('admin.wallets.index'))
        ->assertForbidden();
});

it('lists wallet transactions, including wallet payments for orders', function () {
    $wallet = app(\App\Services\Admin\WalletService::class);
    $order = \App\Models\Order::create([
        'customer_name' => 'A B', 'customer_email' => $this->customer->email, 'shipping_address' => 'x',
        'subtotal' => 25, 'discount_total' => 0, 'shipping_total' => 0, 'tax_total' => 0, 'grand_total' => 25,
        'status' => 'paid', 'payment_status' => 'paid', 'payment_method' => 'wallet', 'placed_at' => now(),
    ]);
    $wallet->debit($this->customer, 25, 'payment', 'Order '.$order->order_number, $order->id);
    $wallet->credit($this->customer, 5, 'adjustment', 'Goodwill credit');

    $html = $this->actingAs($this->admin)->get(route('admin.wallets.index'))->assertOk()->getContent();

    expect($html)->toContain('Wallet Transactions')
        ->toContain('Order payment')
        ->toContain('−$25.00')
        ->toContain('+$5.00')
        ->toContain($order->order_number)
        ->toContain('Goodwill credit')
        ->toContain(route('admin.orders.show', $order->id));
});

it('filters wallet transactions by type and by customer', function () {
    $other = User::factory()->create(['email' => 'other@example.com']);
    $other->assignRole('customer');
    $wallet = app(\App\Services\Admin\WalletService::class);
    $wallet->credit($this->customer, 10, 'topup', 'note-topup-7f3');
    $wallet->credit($other, 7, 'adjustment', 'note-adjust-9k2');

    $this->actingAs($this->admin)
        ->get(route('admin.wallets.index', ['tx_type' => 'topup']))
        ->assertSee('note-topup-7f3')->assertDontSee('note-adjust-9k2');

    $this->actingAs($this->admin)
        ->get(route('admin.wallets.index', ['tx_search' => 'other@example.com']))
        ->assertSee('note-adjust-9k2')->assertDontSee('note-topup-7f3');
});
