<?php

use App\Enums\OrderStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Frontend\OrderTrackingService;
use Illuminate\Support\Carbon;

function trackedOrder(array $attributes = []): Order
{
    return Order::create(array_merge([
        'customer_name' => 'Guest Buyer',
        'customer_email' => 'Guest@Example.com',
        'shipping_address' => '1 Street',
        'shipping_city' => 'Phnom Penh',
        'subtotal' => 20, 'discount_total' => 0, 'shipping_total' => 5, 'tax_total' => 1, 'grand_total' => 26,
        'status' => OrderStatus::Pending,
        'payment_method' => 'aba_qr',
        'payment_status' => 'unpaid',
        'placed_at' => now(),
    ], $attributes));
}

it('finds a guest order by number + email and redirects to a signed page', function () {
    $order = trackedOrder();

    $response = $this->post(route('frontend.orders.track.lookup'), [
        'order_number' => $order->order_number,
        'email' => 'guest@example.com', // case-insensitive
    ]);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/track-order/'.$order->order_number)->toContain('signature=');

    $this->get($response->headers->get('Location'))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Awaiting payment')
        ->assertSee('Waiting for your payment to be confirmed.');
});

it('does not reveal an order when the email does not match', function () {
    $order = trackedOrder();

    $this->from(route('frontend.orders.track'))
        ->post(route('frontend.orders.track.lookup'), ['order_number' => $order->order_number, 'email' => 'someone@else.com'])
        ->assertRedirect(route('frontend.orders.track'))
        ->assertSessionHasErrors('order_number');
});

it('refuses the tracking page without a valid signature', function () {
    $order = trackedOrder();

    $this->get('/track-order/'.$order->order_number)->assertForbidden();
});

it('shows guests a tracking link instead of "View orders" after checkout', function () {
    $order = trackedOrder();

    $this->withSession(['order_id' => $order->id])
        ->get(route('frontend.checkout.confirmation'))
        ->assertOk()
        ->assertSee('Track this order')
        ->assertDontSee('View orders')
        ->assertDontSee('Jun 8 – Jun 10, 2026')
        ->assertSee('Awaiting payment');
});

it('shows a signed-in owner their account order page after checkout', function () {
    $user = User::factory()->create();
    $order = trackedOrder(['user_id' => $user->id]);

    $this->actingAs($user)
        ->withSession(['order_id' => $order->id])
        ->get(route('frontend.checkout.confirmation'))
        ->assertOk()
        ->assertSee(route('frontend.account.orders.show', $order->id), false)
        ->assertDontSee('Track this order');
});

it('builds steps from the real status', function () {
    $tracking = app(OrderTrackingService::class);

    $pending = collect($tracking->steps(trackedOrder()));
    expect($pending->firstWhere('current', true)['label'])->toBe('Order placed')
        ->and($pending->where('done', true))->toHaveCount(1);

    $shipped = collect($tracking->steps(trackedOrder(['status' => OrderStatus::Shipped, 'payment_status' => 'paid', 'paid_at' => now(), 'shipped_at' => now()])));
    expect($shipped->firstWhere('current', true)['label'])->toBe('Shipped')
        ->and($shipped->where('done', true))->toHaveCount(4);

    $cancelled = $tracking->steps(trackedOrder(['status' => OrderStatus::Cancelled]));
    expect($cancelled)->toHaveCount(2)->and($cancelled[1]['label'])->toBe('Cancelled');
});

it('estimates delivery from the shipping method in business days', function () {
    ShippingMethod::create(['name' => 'Standard Delivery', 'type' => 'flat', 'rate' => 5, 'delivery_time' => '2-4 business days', 'status' => true]);
    $order = trackedOrder(['shipping_method' => 'Standard Delivery', 'placed_at' => Carbon::parse('2026-10-02')]); // a Friday

    expect(app(OrderTrackingService::class)->estimatedDelivery($order))->toBe('Oct 6 – Oct 8, 2026');
});

it('links guests to the private tracking page from the confirmation email', function () {
    $order = trackedOrder();

    $html = (new OrderConfirmationMail($order))->render();

    expect($html)->toContain('/track-order/'.$order->order_number)->toContain('signature=');
});
