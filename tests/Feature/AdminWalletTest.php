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
