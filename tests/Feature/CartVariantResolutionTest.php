<?php

use App\Exceptions\CheckoutException;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use App\Services\Frontend\CartService;
use App\Services\Frontend\CheckoutService;
use App\Services\Frontend\ProductService;

// A variable product sold in exactly one option: Small / Navy.
beforeEach(function () {
    $category = Category::firstOrCreate(['slug' => 'tees'], ['name' => 'Tees']);
    $small = Size::create(['name' => 'Small', 'code' => 'S', 'sort_order' => 1]);
    $navy = Color::create(['name' => 'Navy', 'code' => 'NVY', 'hex_code' => '#1E3A8A', 'sort_order' => 1]);

    $this->product = Product::factory()->create([
        'category_id' => $category->id,
        'status' => 'active', 'product_type' => 'variable', 'price' => 50, 'discount_type' => null, 'discount_amount' => 0,
    ]);
    $this->variant = ProductVariant::create([
        'product_id' => $this->product->id, 'size_id' => $small->id, 'color_id' => $navy->id,
        'sku' => 'SKU-S-NVY', 'stock' => 5, 'price' => 40, 'status' => true,
    ]);

    $this->customer = User::factory()->create();
});

function orderPayload(array $items): array
{
    return [
        'customer' => ['first_name' => 'A', 'last_name' => 'B', 'email' => 'buyer@example.com', 'address' => 'x', 'city' => 'y'],
        'items' => $items,
        'shipping_id' => null,
        'payment' => 'card',
    ];
}

it('keeps the variant id when the cart is synced over HTTP', function () {
    $this->actingAs($this->customer)
        ->postJson(route('frontend.cart.sync'), ['items' => [
            ['id' => $this->product->id, 'variant_id' => $this->variant->id, 'size' => 'S', 'color' => 'nvy', 'qty' => 1],
        ]])
        ->assertOk()
        ->assertJsonPath('items.0.variant_id', $this->variant->id);

    expect(Cart::where('user_id', $this->customer->id)->first()->items()->value('product_variant_id'))
        ->toBe($this->variant->id);
});

it('resolves a missing variant id from the size code and colour key', function () {
    $lines = app(CartService::class)->sync($this->customer, [
        ['id' => $this->product->id, 'size' => 'S', 'color' => 'nvy', 'qty' => 1],
    ]);

    expect($lines[0]['variant_id'])->toBe($this->variant->id);
});

it('quick add uses a real option of the product, not a hard-coded size', function () {
    $mapped = app(ProductService::class)->map($this->product->fresh(app(ProductService::class)->relations()));

    expect($mapped['quick_add'])->toBe(['size' => 'S', 'color' => 'nvy', 'variant_id' => $this->variant->id]);
});

it('places an order for a line identified by size code and colour key only', function () {
    $order = app(CheckoutService::class)->placeOrder(orderPayload([
        ['id' => $this->product->id, 'size' => 'S', 'color' => 'nvy', 'qty' => 2],
    ]));

    expect($order->details()->first()->product_variant_id)->toBe($this->variant->id)
        ->and($this->variant->fresh()->stock)->toBe(3);
});

it('still rejects an option the product does not sell', function () {
    expect(fn () => app(CheckoutService::class)->placeOrder(orderPayload([
        ['id' => $this->product->id, 'size' => 'M', 'color' => 'nvy', 'qty' => 1],
    ])))->toThrow(CheckoutException::class);

    expect($this->variant->fresh()->stock)->toBe(5);
});

it('lets a guest check the bag: fills the variant id and flags unsold options', function () {
    $this->postJson(route('frontend.cart.check'), ['items' => [
        ['key' => 'ok', 'id' => $this->product->id, 'size' => 'S', 'color' => 'nvy', 'qty' => 1],
        ['key' => 'stale', 'id' => $this->product->id, 'size' => 'M', 'color' => 'nvy', 'qty' => 1],
        ['key' => 'gone', 'id' => 999999, 'size' => 'S', 'color' => 'nvy', 'qty' => 1],
    ]])
        ->assertOk()
        ->assertExactJson(['items' => [
            ['key' => 'ok', 'variant_id' => $this->variant->id, 'available' => true],
            ['key' => 'stale', 'variant_id' => null, 'available' => false],
            ['key' => 'gone', 'variant_id' => null, 'available' => false],
        ]]);
});

it('ignores a variant id that belongs to another product', function () {
    $other = Product::factory()->create(['category_id' => $this->product->category_id, 'status' => 'active', 'product_type' => 'variable']);

    $result = app(CartService::class)->check([
        ['key' => 'k', 'id' => $other->id, 'variant_id' => $this->variant->id, 'size' => 'S', 'color' => 'nvy'],
    ]);

    expect($result[0])->toBe(['key' => 'k', 'variant_id' => null, 'available' => false]);
});

it('treats a single product line without a variant as available', function () {
    $single = Product::factory()->create(['category_id' => $this->product->category_id, 'status' => 'active', 'product_type' => 'single']);

    $result = app(CartService::class)->check([
        ['key' => 'k', 'id' => $single->id, 'size' => 'One Size', 'color' => 'black'],
    ]);

    expect($result[0]['available'])->toBeTrue();
});
