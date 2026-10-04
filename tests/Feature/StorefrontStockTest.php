<?php

use App\Exceptions\CheckoutException;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Services\Frontend\CartService;
use App\Services\Frontend\CheckoutService;
use App\Services\Frontend\ProductService;

beforeEach(function () {
    $this->category = Category::firstOrCreate(['slug' => 'tees'], ['name' => 'Tees']);
    $this->small = Size::create(['name' => 'Small', 'code' => 'S', 'sort_order' => 1]);
    $this->medium = Size::create(['name' => 'Medium', 'code' => 'M', 'sort_order' => 2]);
    $this->navy = Color::create(['name' => 'Navy', 'code' => 'NVY', 'hex_code' => '#1E3A8A', 'sort_order' => 1]);
});

function stockVariant(Product $product, Size $size, Color $color, int $stock, bool $active = true): ProductVariant
{
    return ProductVariant::create([
        'product_id' => $product->id, 'size_id' => $size->id, 'color_id' => $color->id,
        'sku' => 'SKU-'.$product->id.'-'.$size->code, 'stock' => $stock, 'price' => 40, 'status' => $active,
    ]);
}

function mapProduct(Product $product): array
{
    $svc = app(ProductService::class);

    return $svc->map($product->fresh($svc->relations()));
}

it('exposes per-option stock and picks an in-stock option for quick add', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active', 'product_type' => 'variable']);
    stockVariant($product, $this->small, $this->navy, 0);
    $m = stockVariant($product, $this->medium, $this->navy, 3);

    $mapped = mapProduct($product);

    expect($mapped['variant_stock'])->toBe(['s|nvy' => 0, 'm|nvy' => 3])
        ->and($mapped['stock'])->toBe(3)
        ->and($mapped['in_stock'])->toBeTrue()
        ->and($mapped['quick_add'])->toMatchArray(['size' => 'M', 'variant_id' => $m->id, 'stock' => 3]);
});

it('ignores inactive variants when counting stock', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active', 'product_type' => 'variable']);
    stockVariant($product, $this->small, $this->navy, 9, active: false);

    $mapped = mapProduct($product);

    expect($mapped['in_stock'])->toBeFalse()->and($mapped['variant_stock'])->toBe([]);
});

it('marks a sold-out single product on its page and card', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id, 'status' => 'active', 'product_type' => 'single', 'stock' => 0,
    ]);

    expect(mapProduct($product))->toMatchArray(['stock' => 0, 'in_stock' => false]);

    $html = $this->get(mapProduct($product)['url'])->assertOk()->getContent();
    expect($html)->toContain('ut-tag-soldout')
        ->and($html)->toContain('This product is sold out.')
        ->and($html)->toMatch('/<button[^>]*ut-purchase-add[^>]*disabled/');

    $card = view('components.frontend.product-card', ['product' => mapProduct($product)])->render();
    expect($card)->toContain('Sold out')->and($card)->not->toContain('data-add-to-cart');
});

it('reports each bag line\'s available stock', function () {
    $variable = Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active', 'product_type' => 'variable']);
    stockVariant($variable, $this->small, $this->navy, 2);
    $single = Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active', 'product_type' => 'single', 'stock' => 0]);

    $result = app(CartService::class)->check([
        ['key' => 'v', 'id' => $variable->id, 'size' => 'S', 'color' => 'nvy', 'qty' => 5],
        ['key' => 's', 'id' => $single->id, 'size' => 'One Size', 'color' => 'black', 'qty' => 1],
    ]);

    expect($result[0])->toMatchArray(['available' => true, 'stock' => 2])
        ->and($result[1])->toMatchArray(['available' => true, 'stock' => 0]);
});

it('says sold out (not "0 left") when checkout finds no stock', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id, 'status' => 'active', 'product_type' => 'single', 'stock' => 0, 'name' => 'Zero Tee',
    ]);

    expect(fn () => app(CheckoutService::class)->placeOrder([
        'customer' => ['first_name' => 'A', 'last_name' => 'B', 'email' => 'b@example.com', 'address' => 'x', 'city' => 'y'],
        'items' => [['id' => $product->id, 'qty' => 1]],
        'shipping_id' => null,
        'payment' => 'card',
    ]))->toThrow(CheckoutException::class, '"Zero Tee" is sold out. Please remove it from your bag.');
});
