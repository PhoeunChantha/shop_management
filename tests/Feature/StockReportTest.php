<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

/**
 * Regression: with zero low-stock single products the mapped product rows stay
 * an empty Eloquent collection, whose merge() called getKey() on the variant
 * array rows and crashed the report with a 500.
 */
function seedLowStockVariantOnly(): ProductVariant
{
    $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);

    // A healthy single product so the "single" side of the report is empty.
    Product::factory()->create([
        'category_id' => $category->id,
        'product_type' => 'single',
        'stock' => 50,
        'low_stock_alert' => 5,
    ]);

    $parent = Product::factory()->create([
        'category_id' => $category->id,
        'product_type' => 'variable',
        'stock' => 0,
        'low_stock_alert' => 0,
    ]);

    return ProductVariant::create([
        'product_id' => $parent->id,
        'sku' => 'VAR-LOW-1',
        'stock' => 1,
        'low_stock_alert' => 5,
        'price' => 19.99,
        'cost_price' => 9.00,
        'status' => true,
    ]);
}

it('renders the stock report when only variants are low on stock', function () {
    $variant = seedLowStockVariantOnly();

    $this->actingAs($this->admin)
        ->get(route('admin.reports.stock'))
        ->assertOk()
        ->assertSee($variant->sku);
});

it('exports the stock report when only variants are low on stock', function () {
    $variant = seedLowStockVariantOnly();

    $response = $this->actingAs($this->admin)->get(route('admin.reports.stock.export'));

    $response->assertOk();
    expect($response->streamedContent())->toContain($variant->sku);
});

it('renders the dashboard low-stock widget when only variants are low on stock', function () {
    seedLowStockVariantOnly();

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});
