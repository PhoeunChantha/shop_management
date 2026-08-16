<?php

use App\Models\User;
use App\Models\WalletTopup;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

function customer(): User
{
    $user = User::factory()->create(['wallet_balance' => 0]);
    $user->assignRole('customer');

    return $user;
}

it('creates a pending manual top-up without crediting the wallet', function () {
    $user = customer();

    $this->actingAs($user)
        ->post(route('frontend.account.wallet.topup'), [
            'amount' => 25,
            'payment_method' => 'manual_qr',
        ])
        ->assertRedirect(route('frontend.account.wallet'));

    $topup = WalletTopup::first();
    expect($topup)->not->toBeNull()
        ->and($topup->status)->toBe('pending')
        ->and($topup->method_type)->toBe('manual');

    // Balance is untouched until an admin approves.
    expect((float) $user->fresh()->wallet_balance)->toBe(0.0);
});

it('rejects an unknown payment method', function () {
    $this->actingAs(customer())
        ->post(route('frontend.account.wallet.topup'), ['amount' => 25, 'payment_method' => 'nope'])
        ->assertSessionHasErrors('payment_method');
});

it('lets an admin approve a manual top-up and credits the wallet', function () {
    $buyer = customer();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $topup = WalletTopup::create([
        'user_id' => $buyer->id,
        'tran_id' => 'WT-TEST-1',
        'payment_method' => 'manual_qr',
        'method_type' => 'manual',
        'amount' => 40,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.wallets.topups.approve', $topup))
        ->assertRedirect();

    expect($topup->fresh()->status)->toBe('completed')
        ->and((float) $buyer->fresh()->wallet_balance)->toBe(40.0);
});

it('does not double-credit when approving twice', function () {
    $buyer = customer();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $topup = WalletTopup::create([
        'user_id' => $buyer->id,
        'tran_id' => 'WT-TEST-2',
        'payment_method' => 'manual_qr',
        'method_type' => 'manual',
        'amount' => 15,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)->post(route('admin.wallets.topups.approve', $topup));
    $this->actingAs($admin)->post(route('admin.wallets.topups.approve', $topup));

    expect((float) $buyer->fresh()->wallet_balance)->toBe(15.0);
});

it('lets an admin reject a manual top-up without crediting', function () {
    $buyer = customer();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $topup = WalletTopup::create([
        'user_id' => $buyer->id,
        'tran_id' => 'WT-TEST-3',
        'payment_method' => 'manual_qr',
        'method_type' => 'manual',
        'amount' => 30,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.wallets.topups.reject', $topup), ['note' => 'No payment received']);

    expect($topup->fresh()->status)->toBe('failed')
        ->and((float) $buyer->fresh()->wallet_balance)->toBe(0.0);
});
