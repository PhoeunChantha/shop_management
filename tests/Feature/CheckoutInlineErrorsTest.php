<?php

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;

beforeEach(function () {
    $category = Category::firstOrCreate(['slug' => 'tees'], ['name' => 'Tees']);
    $small = Size::create(['name' => 'Small', 'code' => 'S', 'sort_order' => 1]);
    $navy = Color::create(['name' => 'Navy', 'code' => 'NVY', 'hex_code' => '#1E3A8A', 'sort_order' => 1]);

    $this->product = Product::factory()->create([
        'category_id' => $category->id, 'status' => 'active', 'product_type' => 'variable',
    ]);
    ProductVariant::create([
        'product_id' => $this->product->id, 'size_id' => $small->id, 'color_id' => $navy->id,
        'sku' => 'SKU-S-NVY', 'stock' => 5, 'price' => 40, 'status' => true,
    ]);
});

it('shows an order error inside the checkout form, not as a toast', function () {
    $this->from(route('frontend.checkout.index'))
        ->post(route('frontend.checkout.store'), [
            'email' => 'buyer@example.com', 'first_name' => 'A', 'last_name' => 'B',
            'address' => 'x', 'city' => 'y',
            'items' => json_encode([['id' => $this->product->id, 'size' => 'M', 'color' => 'nvy', 'qty' => 1]]),
        ])
        ->assertRedirect(route('frontend.checkout.index'));

    $page = $this->get(route('frontend.checkout.index'))->assertOk();

    $html = $page->getContent();
    expect($html)->toContain('id="coAlert"')
        ->and(substr_count($html, 'is no longer available in the selected option'))->toBe(1)
        ->and($html)->not->toContain('window.toastr.error(');
});

it('keeps the alert hidden when there is no order error', function () {
    $html = $this->get(route('frontend.checkout.index'))->assertOk()->getContent();

    expect($html)->toMatch('/id="coAlert"[^>]*\shidden\s*>/')
        ->and($html)->toContain('id="couponMsg"');
});
