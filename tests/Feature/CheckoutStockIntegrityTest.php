<?php

use App\Exceptions\CheckoutException;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\Frontend\CheckoutService;

function checkoutPayload(array $items, ?string $email = 'buyer@example.com'): array
{
    return [
        'customer' => ['first_name' => 'A', 'last_name' => 'B', 'email' => $email, 'address' => 'x', 'city' => 'y'],
        'items' => $items,
        'shipping_id' => null,
        'payment' => 'card',
    ];
}

function variableProductWithVariant(int $stock, bool $variantActive = true): array
{
    $category = Category::firstOrCreate(['slug' => 'tees'], ['name' => 'Tees']);

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'status' => 'active', 'product_type' => 'variable', 'price' => 50, 'discount_type' => null, 'discount_amount' => 0,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id, 'sku' => 'SKU-'.$product->id, 'stock' => $stock, 'price' => 40, 'status' => $variantActive,
    ]);

    return [$product, $variant];
}

it('rejects an order line that duplicates a stockable rather than overselling it', function () {
    [$product, $variant] = variableProductWithVariant(stock: 5);

    $payload = checkoutPayload([
        ['id' => $product->id, 'variant_id' => $variant->id, 'qty' => 4],
        ['id' => $product->id, 'variant_id' => $variant->id, 'qty' => 4],
    ]);

    expect(fn () => app(CheckoutService::class)->placeOrder($payload))
        ->toThrow(CheckoutException::class);

    // Nothing was decremented — the whole order was rejected atomically.
    expect($variant->fresh()->stock)->toBe(5);
});

it('decrements a duplicated stockable by the combined quantity when stock is sufficient', function () {
    [$product, $variant] = variableProductWithVariant(stock: 10);

    $payload = checkoutPayload([
        ['id' => $product->id, 'variant_id' => $variant->id, 'qty' => 3],
        ['id' => $product->id, 'variant_id' => $variant->id, 'qty' => 4],
    ]);

    $order = app(CheckoutService::class)->placeOrder($payload);

    expect($variant->fresh()->stock)->toBe(3)
        ->and($order->details()->sum('quantity'))->toBe(7)
        ->and(StockMovement::where('variant_id', $variant->id)->count())->toBe(1);
});

it('rejects checkout when the cart variant id no longer matches any current variant', function () {
    [$product, $variant] = variableProductWithVariant(stock: 5);
    $deletedVariantId = $variant->id + 999;

    $payload = checkoutPayload([
        ['id' => $product->id, 'variant_id' => $deletedVariantId, 'qty' => 1],
    ]);

    expect(fn () => app(CheckoutService::class)->placeOrder($payload))
        ->toThrow(CheckoutException::class);

    // The still-existing variant's stock must be untouched — it was never sold.
    expect($variant->fresh()->stock)->toBe(5);
});

it('rejects checkout for a variable product line with no variant id and no size/color', function () {
    [$product, $variant] = variableProductWithVariant(stock: 5);

    $payload = checkoutPayload([
        ['id' => $product->id, 'qty' => 1],
    ]);

    expect(fn () => app(CheckoutService::class)->placeOrder($payload))
        ->toThrow(CheckoutException::class);

    expect($variant->fresh()->stock)->toBe(5);
});

it('rejects checkout when the referenced variant has since been deactivated', function () {
    [$product, $variant] = variableProductWithVariant(stock: 5, variantActive: false);

    $payload = checkoutPayload([
        ['id' => $product->id, 'variant_id' => $variant->id, 'qty' => 1],
    ]);

    expect(fn () => app(CheckoutService::class)->placeOrder($payload))
        ->toThrow(CheckoutException::class);

    expect($variant->fresh()->stock)->toBe(5);
});

it('still places a normal single-variant order correctly', function () {
    [$product, $variant] = variableProductWithVariant(stock: 5);

    $payload = checkoutPayload([
        ['id' => $product->id, 'variant_id' => $variant->id, 'qty' => 2],
    ]);

    $order = app(CheckoutService::class)->placeOrder($payload);

    expect($variant->fresh()->stock)->toBe(3)
        ->and($order->details()->first()->product_variant_id)->toBe($variant->id);
});
