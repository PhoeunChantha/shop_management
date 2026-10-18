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

it('filters payments by gateway, method, amount and date, with stats following', function () {
    $keep = makePayment();
    $keep->update(['tran_id' => 'TX-KEEP-CUTLUY', 'gateway' => 'cutluy', 'payment_option' => 'khqr', 'amount' => 30]);
    $other = makePayment('failed');
    $other->update(['tran_id' => 'TX-OTHER-FAILED']);
    $old = makePayment();
    $old->update(['tran_id' => 'TX-OLD-MONTHS']);
    $old->forceFill(['created_at' => now()->subMonths(2)])->save();

    $this->actingAs($this->admin)
        ->get(route('admin.payments.index', [
            'gateway' => 'cutluy',
            'payment_option' => 'khqr',
            'amount' => '10-50',
            'date_from' => now()->subWeek()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertSee($keep->tran_id)
        ->assertDontSee($other->tran_id)
        ->assertDontSee($old->tran_id)
        ->assertSee('data-count-up="1"', false)
        ->assertSee('data-count-up="30"', false);
});

it('offers gateway, method, currency, amount and date filters', function () {
    makePayment();

    $this->actingAs($this->admin)
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertSee('name="gateway"', false)
        ->assertSee('name="payment_option"', false)
        ->assertSee('name="currency"', false)
        ->assertSee('name="amount"', false)
        ->assertSee('name="date_from"', false)
        ->assertSee('order-stats', false);
});

it('rejects an unknown amount range', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.payments.index', ['amount' => '5-6']))
        ->assertSessionHasErrors('amount');
});

it('shows payment amounts with the currency symbol instead of the code', function () {
    makePayment()->update(['amount' => 1.08, 'currency' => 'USD']);

    $this->actingAs($this->admin)
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertSee('$1.08')
        ->assertDontSee('USD 1.08');
});
