<?php

use App\Exceptions\CheckoutException;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\Frontend\CheckoutService;
use App\Services\Frontend\ProductService;

beforeEach(function () {
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);
});

it('surfaces best sellers first in cross-sell picks', function () {
    Product::factory()->create(['status' => 'active', 'is_best_seller' => false, 'is_featured' => false]);
    $best = Product::factory()->create(['status' => 'active', 'is_best_seller' => true]);

    $cross = app(ProductService::class)->crossSell(4);

    expect($cross->first()['id'])->toBe($best->id);
});

it('rejects an order that exceeds available stock', function () {
    $product = Product::factory()->create([
        'status' => 'active',
        'product_type' => 'single',
        'stock' => 1,
        'price' => 20,
    ]);

    $place = fn () => app(CheckoutService::class)->placeOrder([
        'customer' => ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'address' => 'x', 'city' => 'y'],
        'items' => [['id' => $product->id, 'name' => 'x', 'price' => 20, 'size' => 'M', 'color' => 'black', 'qty' => 2]],
        'shipping_id' => null,
        'payment' => 'card',
    ]);

    expect($place)->toThrow(CheckoutException::class);
    expect(Order::count())->toBe(0);
});

it('places the order when stock is sufficient', function () {
    $product = Product::factory()->create([
        'status' => 'active',
        'product_type' => 'single',
        'stock' => 5,
        'price' => 20,
    ]);

    $order = app(CheckoutService::class)->placeOrder([
        'customer' => ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'address' => 'x', 'city' => 'y'],
        'items' => [['id' => $product->id, 'name' => 'x', 'price' => 20, 'size' => 'M', 'color' => 'black', 'qty' => 2]],
        'shipping_id' => null,
        'payment' => 'card',
    ]);

    expect($order->exists)->toBeTrue();
    expect((int) $product->refresh()->stock)->toBe(3);
});
