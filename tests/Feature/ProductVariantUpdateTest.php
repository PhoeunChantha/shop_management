<?php

use App\Enums\StockMovementType;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->category = Category::create(['name' => 'Tees', 'slug' => 'tees']);
    $this->attr = Attribute::create(['name' => 'Test Size', 'slug' => 'test-size', 'type' => 'custom', 'status' => true, 'sort_order' => 99]);
    $this->valM = $this->attr->values()->create(['value' => 'M', 'slug' => 'test-m', 'status' => true]);
});

function variableProduct($category): Product
{
    return Product::factory()->create([
        'category_id' => $category->id, 'product_type' => 'variable',
        'status' => 'active', 'price' => 100, 'discount_type' => null, 'discount_amount' => 0,
    ]);
}

function makeVariant(Product $product, int $valueId, string $sku, int $stock): ProductVariant
{
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => $sku, 'stock' => $stock, 'status' => true]);
    $variant->values()->sync([$valueId]);

    return $variant;
}

it('updates a referenced variant in place instead of delete-and-recreate', function () {
    $product = variableProduct($this->category);
    $variant = makeVariant($product, $this->valM->id, 'SIZE-M', 5);

    // History that would block/destroy a delete (this is what caused the 500).
    StockMovement::create([
        'product_id' => $product->id, 'variant_id' => $variant->id,
        'type' => StockMovementType::Sale, 'quantity' => -1, 'stock_after' => 5,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.products.update', $product->id), [
            'category_id' => $this->category->id,
            'name' => ['en' => 'Heavy Tee'],
            'product_type' => 'variable',
            'price' => 120,
            'status' => 'active',
            'variants' => [
                ['value_ids' => [$this->valM->id], 'sku' => 'SIZE-M', 'stock' => 9, 'status' => 1],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    // Same row (id preserved), updated in place — no FK violation.
    expect(ProductVariant::where('product_id', $product->id)->count())->toBe(1);
    $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 9]);
    $this->assertDatabaseHas('stock_movements', ['variant_id' => $variant->id]);
});

it('deactivates a referenced variant that is removed (keeps history)', function () {
    $product = variableProduct($this->category);
    $valL = $this->attr->values()->create(['value' => 'L', 'slug' => 'l', 'status' => true]);

    $variantM = makeVariant($product, $this->valM->id, 'SIZE-M', 5);
    $variantL = makeVariant($product, $valL->id, 'SIZE-L', 3);

    StockMovement::create([
        'product_id' => $product->id, 'variant_id' => $variantM->id,
        'type' => StockMovementType::Sale, 'quantity' => -1, 'stock_after' => 5,
    ]);

    // Resubmit with only L — M is dropped but referenced, so it deactivates.
    $this->actingAs($this->admin)
        ->put(route('admin.products.update', $product->id), [
            'category_id' => $this->category->id,
            'name' => ['en' => 'Heavy Tee'],
            'product_type' => 'variable',
            'price' => 100,
            'status' => 'active',
            'variants' => [
                ['value_ids' => [$valL->id], 'sku' => 'SIZE-L', 'stock' => 3, 'status' => 1],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('product_variants', ['id' => $variantM->id]);
    expect($variantM->fresh()->status)->toBeFalse();
});

it('deletes an unreferenced variant that is removed', function () {
    $product = variableProduct($this->category);
    $valL = $this->attr->values()->create(['value' => 'L', 'slug' => 'l', 'status' => true]);

    $variantM = makeVariant($product, $this->valM->id, 'SIZE-M', 5);
    $variantL = makeVariant($product, $valL->id, 'SIZE-L', 3);

    $this->actingAs($this->admin)
        ->put(route('admin.products.update', $product->id), [
            'category_id' => $this->category->id,
            'name' => ['en' => 'Heavy Tee'],
            'product_type' => 'variable',
            'price' => 100,
            'status' => 'active',
            'variants' => [
                ['value_ids' => [$this->valM->id], 'sku' => 'SIZE-M', 'stock' => 5, 'status' => 1],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('product_variants', ['id' => $variantL->id]);
    $this->assertDatabaseHas('product_variants', ['id' => $variantM->id]);
});
