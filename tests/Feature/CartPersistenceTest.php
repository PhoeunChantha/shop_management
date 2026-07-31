<?php

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Frontend\CartService;
use App\Services\Frontend\CheckoutService;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);

    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
});

it('persists and reconciles the cart server-side', function () {
    $product = Product::factory()->create(['status' => 'active', 'price' => 20]);

    $lines = app(CartService::class)->sync($this->customer, [
        ['id' => $product->id, 'size' => 'M', 'color' => 'black', 'qty' => 2],
    ]);

    expect($lines)->toHaveCount(1)
        ->and($lines[0]['id'])->toBe($product->id)
        ->and($lines[0]['qty'])->toBe(2)
        ->and($lines[0]['key'])->toBe($product->id.'-M-black');

    expect(Cart::where('user_id', $this->customer->id)->first()->items()->count())->toBe(1);
});

it('drops inactive and missing products on sync', function () {
    $active = Product::factory()->create(['status' => 'active']);
    $inactive = Product::factory()->create(['status' => 'draft']);

    $lines = app(CartService::class)->sync($this->customer, [
        ['id' => $active->id, 'size' => 'M', 'color' => 'black', 'qty' => 1],
        ['id' => $inactive->id, 'size' => 'M', 'color' => 'black', 'qty' => 1],
        ['id' => 999999, 'size' => 'M', 'color' => 'black', 'qty' => 1],
    ]);

    expect($lines)->toHaveCount(1)->and($lines[0]['id'])->toBe($active->id);
});

it('sums duplicate lines on sync', function () {
    $product = Product::factory()->create(['status' => 'active']);

    $lines = app(CartService::class)->sync($this->customer, [
        ['id' => $product->id, 'size' => 'M', 'color' => 'black', 'qty' => 1],
        ['id' => $product->id, 'size' => 'M', 'color' => 'black', 'qty' => 3],
    ]);

    expect($lines)->toHaveCount(1)->and($lines[0]['qty'])->toBe(4);
});

it('clears the saved cart when an order is placed', function () {
    $product = Product::factory()->create(['status' => 'active', 'product_type' => 'single', 'stock' => 5, 'price' => 20]);
    app(CartService::class)->sync($this->customer, [['id' => $product->id, 'size' => 'M', 'color' => 'black', 'qty' => 1]]);

    expect(Cart::where('user_id', $this->customer->id)->first()->items()->count())->toBe(1);

    $this->actingAs($this->customer);
    app(CheckoutService::class)->placeOrder([
        'customer' => ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'address' => 'x', 'city' => 'y'],
        'items' => [['id' => $product->id, 'name' => 'x', 'price' => 20, 'size' => 'M', 'color' => 'black', 'qty' => 1]],
        'shipping_id' => null,
        'payment' => 'card',
    ]);

    expect(Cart::where('user_id', $this->customer->id)->first()->items()->count())->toBe(0);
});

it('requires authentication to sync the cart', function () {
    $this->post(route('frontend.cart.sync'), ['items' => []])
        ->assertRedirect(route('frontend.login'));
});
