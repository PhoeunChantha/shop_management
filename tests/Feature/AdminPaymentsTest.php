<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

function makePayment(string $status = 'completed'): Payment
{
    $order = Order::factory()->create(['grand_total' => 25]);

    return Payment::create([
        'order_id' => $order->id,
        'gateway' => 'payway',
        'tran_id' => $order->order_number,
        'payment_option' => 'cards',
        'amount' => 25,
        'currency' => 'USD',
        'status' => $status,
    ]);
}

it('lists payments for an admin', function () {
    $payment = makePayment();

    $this->actingAs($this->admin)
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertSee($payment->tran_id)
        ->assertSee('PAYWAY');
});

it('forbids a customer without permission', function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)
        ->get(route('admin.payments.index'))
        ->assertForbidden();
});

it('exports payments as CSV', function () {
    makePayment();

    $response = $this->actingAs($this->admin)->get(route('admin.payments.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});
