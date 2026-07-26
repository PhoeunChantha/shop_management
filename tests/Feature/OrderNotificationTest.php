<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Admin\OrderService;
use App\Services\Frontend\AccountService;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
});

function shipOrder(User $customer): Order
{
    $order = Order::factory()->status(OrderStatus::Pending)->create([
        'user_id' => $customer->id,
        'payment_status' => 'unpaid',
    ]);

    app(OrderService::class)->updateFulfilment($order, [
        'status' => OrderStatus::Shipped->value,
        'fulfillment_status' => 'fulfilled',
    ]);

    return $order;
}

it('notifies the customer when an order status changes', function () {
    shipOrder($this->customer);

    expect($this->customer->notifications()->count())->toBe(1);
    $data = $this->customer->notifications()->first()->data;
    expect($data['type'])->toBe('order')
        ->and($data['status'])->toBe('Shipped');
});

it('does not notify when the status is unchanged', function () {
    $order = Order::factory()->status(OrderStatus::Processing)->create([
        'user_id' => $this->customer->id,
        'payment_status' => 'unpaid',
    ]);

    app(OrderService::class)->updateFulfilment($order, [
        'status' => OrderStatus::Processing->value,
        'fulfillment_status' => 'unfulfilled',
    ]);

    expect($this->customer->notifications()->count())->toBe(0);
});

it('exposes notifications and the unread count via the account service', function () {
    shipOrder($this->customer);
    $this->actingAs($this->customer);

    $account = app(AccountService::class);
    expect($account->notifications())->toHaveCount(1)
        ->and($account->unreadNotifications())->toBe(1);
});

it('marks a single notification read', function () {
    shipOrder($this->customer);
    $id = $this->customer->notifications()->first()->id;

    $this->actingAs($this->customer)
        ->patch(route('frontend.account.notifications.read', $id))
        ->assertRedirect();

    expect($this->customer->unreadNotifications()->count())->toBe(0);
});

it('marks all notifications read', function () {
    shipOrder($this->customer);

    $this->actingAs($this->customer)
        ->post(route('frontend.account.notifications.read-all'))
        ->assertRedirect();

    expect($this->customer->unreadNotifications()->count())->toBe(0);
});
