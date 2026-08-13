<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Services\Frontend\CheckoutService;

beforeEach(function () {
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);
});

it('returns JSON search suggestions matching the term', function () {
    Product::factory()->create(['status' => 'active', 'name' => 'Midnight Hoodie']);
    Product::factory()->create(['status' => 'active', 'name' => 'Sunrise Shorts']);

    $response = $this->getJson(route('frontend.shop.search', ['q' => 'Midnight']))->assertOk();

    $names = collect($response->json('results'))->pluck('name');
    expect($names)->toContain('Midnight Hoodie')
        ->and($names)->not->toContain('Sunrise Shorts');

    $response->assertJsonStructure(['results' => [['name', 'url', 'price', 'image']]]);
});

it('returns no suggestions for a blank query', function () {
    Product::factory()->create(['status' => 'active']);

    $this->getJson(route('frontend.shop.search', ['q' => '']))
        ->assertOk()
        ->assertExactJson(['results' => []]);
});

it('emits Product and BreadcrumbList JSON-LD on the product page', function () {
    $product = Product::factory()->create(['status' => 'active', 'name' => 'Structured Data Tee']);

    $this->get(route('frontend.shop.show', $product->slug))
        ->assertOk()
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type":"Product"', false)
        ->assertSee('"@type":"BreadcrumbList"', false);
});

it('serves a dynamic robots.txt with an absolute sitemap URL', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Sitemap: http', false)
        ->assertSee('Disallow: /admin', false);
});

it('renders the home page from the bounded pool without error', function () {
    Product::factory()->count(5)->create(['status' => 'active']);

    $this->get(route('frontend.home'))->assertOk();
});

it('sells the exact variant (price + stock) when the cart supplies a variant_id', function () {
    $size = Size::create(['name' => 'Large', 'code' => 'L', 'status' => true]);
    $color = Color::create(['name' => 'Black', 'code' => 'black', 'hex_code' => '#000000', 'status' => true]);
    $product = Product::factory()->create([
        'status' => 'active', 'product_type' => 'variable', 'price' => 100,
        'discount_type' => null, 'discount_amount' => 0,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id, 'size_id' => $size->id, 'color_id' => $color->id,
        'sku' => 'SKU-L-BLACK', 'stock' => 3, 'price' => 80, 'status' => true,
    ]);

    // Deliberately wrong size/color labels — the explicit variant_id must win.
    $order = app(CheckoutService::class)->placeOrder([
        'customer' => ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'address' => 'x', 'city' => 'y'],
        'items' => [['id' => $product->id, 'variant_id' => $variant->id, 'size' => 'ZZZ', 'color' => 'ZZZ', 'qty' => 1]],
        'shipping_id' => null,
        'payment' => 'card',
    ]);

    $detail = $order->details()->first();
    expect((float) $detail->price)->toBe(80.0)
        ->and($detail->product_variant_id)->toBe($variant->id)
        ->and($variant->refresh()->stock)->toBe(2);
});
