<?php

use App\Models\Setting;
use App\Models\User;
use App\Models\WalletTopup;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
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

it('renders the wallet page with the payslip upload field', function () {
    $this->actingAs(customer())
        ->get(route('frontend.account.wallet'))
        ->assertOk()
        ->assertSee('Upload your payment payslip');
});

it('shows the payment QR when a manual method has one configured', function () {
    Setting::set('payment_methods', json_encode([
        [
            'id' => 'manual_qr', 'name' => 'ABA QR', 'code' => 'manual_qr', 'type' => 'manual',
            'status' => true, 'sort_order' => 1, 'qr_image' => 'https://example.com/qr.png',
        ],
    ]), 'payment');

    $this->actingAs(customer())
        ->get(route('frontend.account.wallet'))
        ->assertOk()
        ->assertSee('Payment QR');
});

it('creates a pending manual top-up with a payslip and without crediting the wallet', function () {
    $user = customer();

    $this->actingAs($user)
        ->post(route('frontend.account.wallet.topup'), [
            'amount' => 25,
            'payment_method' => 'manual_qr',
            'payslip' => UploadedFile::fake()->image('payslip.jpg'),
        ])
        ->assertRedirect(route('frontend.account.wallet'));

    $topup = WalletTopup::first();
    expect($topup)->not->toBeNull()
        ->and($topup->status)->toBe('pending')
        ->and($topup->method_type)->toBe('manual')
        ->and($topup->payslip)->not->toBeNull();

    // Balance is untouched until an admin approves.
    expect((float) $user->fresh()->wallet_balance)->toBe(0.0);
});

it('rejects a manual top-up without a payslip', function () {
    $this->actingAs(customer())
        ->post(route('frontend.account.wallet.topup'), [
            'amount' => 25,
            'payment_method' => 'manual_qr',
        ])
        ->assertSessionHasErrors('payslip');

    expect(WalletTopup::count())->toBe(0);
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
