<?php

use App\Models\Order;
use App\Models\User;

beforeEach(function () {
    $this->customer = User::factory()->create();
});

it('downloads a PDF invoice for the customer own order', function () {
    $order = Order::factory()->create(['user_id' => $this->customer->id]);
    $order->details()->create([
        'name' => 'Heavy Tee',
        'sku' => 'HT-1',
        'price' => 25,
        'quantity' => 2,
        'line_total' => 50,
    ]);

    $response = $this->actingAs($this->customer)
        ->get(route('frontend.account.orders.invoice', $order->id));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))
        ->toContain('invoice-'.$order->order_number.'.pdf');
});

it('blocks a guest from the invoice route', function () {
    $order = Order::factory()->create();

    $this->get(route('frontend.account.orders.invoice', $order->id))
        ->assertRedirect(route('frontend.login'));
});

it('404s when the order belongs to another customer', function () {
    $order = Order::factory()->create(); // no user_id / different email

    $this->actingAs($this->customer)
        ->get(route('frontend.account.orders.invoice', $order->id))
        ->assertNotFound();
});
