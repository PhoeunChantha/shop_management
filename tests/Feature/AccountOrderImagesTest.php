<?php

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->customer = User::factory()->create();
});

function orderWithItem(User $user, ?Product $product): Order
{
    $order = Order::factory()->create(['user_id' => $user->id]);
    $order->details()->create([
        'product_id' => $product?->id,
        'name' => 'Heavy Tee',
        'sku' => 'HT-1',
        'price' => 25,
        'quantity' => 1,
        'line_total' => 25,
    ]);

    return $order;
}

it('shows the product image on order history, order detail and the dashboard', function () {
    $category = Category::create(['name' => 'Tees', 'slug' => 'tees']);
    $product = Product::factory()->create(['category_id' => $category->id, 'thumbnail' => 'heavy-tee.webp', 'status' => true]);
    $order = orderWithItem($this->customer, $product);
    $src = 'src="'.e($product->thumbnail_preview_url ?: $product->thumbnail_url).'"';

    foreach ([
        route('frontend.account.orders'),
        route('frontend.account.orders.show', $order->id),
        route('frontend.account.dashboard'),
    ] as $url) {
        expect($this->actingAs($this->customer)->get($url)->assertOk()->getContent())->toContain($src);
    }
});

it('falls back to the placeholder when the item has no product image', function () {
    orderWithItem($this->customer, null);

    $this->actingAs($this->customer)->get(route('frontend.account.orders'))
        ->assertOk()
        ->assertSee('class="ph"', false);
});

it('still renders a delivered order whose product was deleted, without a review link', function () {
    $order = orderWithItem($this->customer, null);
    $order->update(['status' => OrderStatus::Delivered]);

    foreach ([route('frontend.account.orders'), route('frontend.account.orders.show', $order->id)] as $url) {
        $this->actingAs($this->customer)->get($url)->assertOk()->assertDontSee('/review/', false);
    }
});
