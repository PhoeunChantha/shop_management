<?php

use App\Helpers\ImageManager;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use App\Services\Admin\FacebookPostService;
use App\Services\Frontend\ProductService as FrontendProductService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->category = Category::create(['name' => 'Tees', 'slug' => 'tees']);

    // Point EnvService at a scratch .env so the Facebook-publish test below
    // never touches the real one (same pattern as ProductFacebookPublishTest).
    $this->envPath = storage_path('framework/testing-env-'.uniqid().'.env');
    file_put_contents($this->envPath, "APP_NAME=Shop\n");
    app()->loadEnvironmentFrom(basename($this->envPath));
    app()->useEnvironmentPath(dirname($this->envPath));
});

afterEach(function () {
    @unlink($this->envPath);
});

// -- Size + color filter must match the same variant row --------------------------

it('does not match a product whose size and color come from different variants', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active', 'product_type' => 'variable']);
    $sizeM = Size::create(['name' => 'Medium', 'code' => 'M', 'status' => true]);
    $sizeL = Size::create(['name' => 'Large', 'code' => 'L', 'status' => true]);
    $red = Color::create(['name' => 'Red', 'code' => 'red', 'hex_code' => '#ff0000', 'status' => true]);
    $blue = Color::create(['name' => 'Blue', 'code' => 'blue', 'hex_code' => '#0000ff', 'status' => true]);

    // One variant has the filtered size (L) but the wrong color (blue); the
    // other has the filtered color (red) but the wrong size (M) — no single
    // variant is both Large AND Red.
    ProductVariant::create(['product_id' => $product->id, 'size_id' => $sizeL->id, 'color_id' => $blue->id, 'sku' => 'V-L-BLUE', 'stock' => 5, 'status' => true]);
    ProductVariant::create(['product_id' => $product->id, 'size_id' => $sizeM->id, 'color_id' => $red->id, 'sku' => 'V-M-RED', 'stock' => 5, 'status' => true]);

    $results = app(FrontendProductService::class)->filteredProducts(['sizes' => ['L'], 'colors' => ['red']]);

    expect(collect($results->items())->pluck('id'))->not->toContain($product->id);
});

it('matches a product whose size and color are on the same variant', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active', 'product_type' => 'variable']);
    $sizeL = Size::create(['name' => 'Large', 'code' => 'L', 'status' => true]);
    $red = Color::create(['name' => 'Red', 'code' => 'red', 'hex_code' => '#ff0000', 'status' => true]);

    ProductVariant::create(['product_id' => $product->id, 'size_id' => $sizeL->id, 'color_id' => $red->id, 'sku' => 'V-L-RED2', 'stock' => 5, 'status' => true]);

    $results = app(FrontendProductService::class)->filteredProducts(['sizes' => ['L'], 'colors' => ['red']]);

    expect(collect($results->items())->pluck('id'))->toContain($product->id);
});

// -- Price filter/sort must use the discounted price -------------------------------

it('includes a heavily discounted product under a low max_price filter', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id, 'status' => 'active',
        'price' => 200, 'discount_type' => 'percentage', 'discount_amount' => 80, // final_price = 40
    ]);

    $results = app(FrontendProductService::class)->filteredProducts(['max_price' => 50]);

    expect(collect($results->items())->pluck('id'))->toContain($product->id);
});

it('sorts by the discounted price, not the list price', function () {
    $cheaperAfterDiscount = Product::factory()->create([
        'category_id' => $this->category->id, 'status' => 'active',
        'price' => 200, 'discount_type' => 'percentage', 'discount_amount' => 90, 'name' => 'Discounted Expensive Shirt', // final = 20
    ]);
    $pricierNoDiscount = Product::factory()->create([
        'category_id' => $this->category->id, 'status' => 'active',
        'price' => 30, 'discount_type' => null, 'discount_amount' => 0, 'name' => 'Plain Cheap-Listed Shirt', // final = 30
    ]);

    $results = app(FrontendProductService::class)->filteredProducts(['sort' => 'low']);
    $ids = collect($results->items())->pluck('id')->values();

    expect($ids->search($cheaperAfterDiscount->id))->toBeLessThan($ids->search($pricierNoDiscount->id));
});

// -- Duplicate variant combination rejected at validation ---------------------------

it('rejects two submitted variants sharing the same attribute-value combination', function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $size = Size::create(['name' => 'Medium', 'code' => 'M', 'status' => true]);

    $this->post(route('admin.products.store'), [
        'category_id' => $this->category->id,
        'name' => ['en' => 'Dup Variant Product'],
        'product_type' => 'variable',
        'price' => 20,
        'status' => 'active',
        'variants' => [
            ['value_ids' => [$size->id], 'sku' => 'DUP-1', 'stock' => 5, 'status' => 1],
            ['value_ids' => [$size->id], 'sku' => 'DUP-2', 'stock' => 3, 'status' => 1],
        ],
    ])->assertSessionHasErrors('variants.1.value_ids');
});

// -- Facebook publish uses the primary image ----------------------------------------

it('publishes the primary gallery image, not just the first-sorted one', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'thumbnail' => null]);
    File::ensureDirectoryExists(public_path('uploads/products'));
    File::put(public_path('uploads/products/first-sorted.jpg'), 'fake');
    File::put(public_path('uploads/products/actual-primary.jpg'), 'fake');

    ProductImage::create(['product_id' => $product->id, 'image' => 'first-sorted.jpg', 'sort_order' => 1, 'is_primary' => false]);
    ProductImage::create(['product_id' => $product->id, 'image' => 'actual-primary.jpg', 'sort_order' => 2, 'is_primary' => true]);
    $product->load('images');

    enableFacebookPublishing();
    Http::fake(['graph.facebook.com/*' => Http::response(['id' => '1', 'post_id' => '123_456'], 200)]);

    app(FacebookPostService::class)->publish($product);

    Http::assertSent(fn ($request) => str_contains((string) $request->body(), 'actual-primary.jpg'));

    File::delete(public_path('uploads/products/first-sorted.jpg'));
    File::delete(public_path('uploads/products/actual-primary.jpg'));
});

// -- Orphaned thumbnail cleanup ------------------------------------------------------

it('deletes the paired responsive thumbnail when the original image is deleted', function () {
    File::ensureDirectoryExists(public_path('uploads/products'));
    $name = ImageManager::upload(UploadedFile::fake()->image('cleanup-test.jpg', 1000, 1000), 'products');
    $thumb = ImageManager::generateThumbnail($name, 'products', 480, 480);

    expect($thumb)->not->toBeNull();
    expect(File::exists(public_path(ImageManager::path($thumb, 'products'))))->toBeTrue();

    ImageManager::delete($name, 'products');

    expect(File::exists(public_path('uploads/products/'.$name)))->toBeFalse()
        ->and(File::exists(public_path(ImageManager::path($thumb, 'products'))))->toBeFalse();
});
