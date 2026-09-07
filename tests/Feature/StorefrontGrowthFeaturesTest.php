<?php

use App\Enums\OrderStatus;
use App\Helpers\ImageManager;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Services\Admin\MediaAssetService;
use App\Services\Frontend\ProductService as FrontendProductService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->category = Category::create(['name' => 'Tees', 'slug' => 'tees']);
});

// -- Product catalog feed -----------------------------------------------------

it('serves an XML product feed with only active, imaged products', function () {
    Product::factory()->create([
        'category_id' => $this->category->id,
        'status' => 'active',
        'thumbnail' => 'feed-test.jpg',
        'name' => 'Feed Visible Tee',
    ]);
    Product::factory()->create([
        'category_id' => $this->category->id,
        'status' => 'inactive',
        'thumbnail' => 'feed-test.jpg',
        'name' => 'Feed Hidden Tee',
    ]);
    Product::factory()->create([
        'category_id' => $this->category->id,
        'status' => 'active',
        'thumbnail' => null,
        'name' => 'Feed No Image Tee',
    ]);

    $response = $this->get(route('frontend.feed.products'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml');
    $content = $response->getContent();

    expect($content)->toContain('<g:id>')
        ->and($content)->toContain('Feed Visible Tee')
        ->and($content)->not->toContain('Feed Hidden Tee')
        ->and($content)->not->toContain('Feed No Image Tee');
});

it('reports feed availability from real stock', function () {
    Product::factory()->create([
        'category_id' => $this->category->id,
        'status' => 'active',
        'thumbnail' => 'feed-stock.jpg',
        'product_type' => 'single',
        'stock' => 0,
        'name' => 'Feed Zero Stock Tee',
    ]);

    $content = $this->get(route('frontend.feed.products'))->getContent();

    expect($content)->toContain('<g:availability>out of stock</g:availability>');
});

// -- Frequently bought together / related products -----------------------------

it('recommends products frequently bought together over completed orders only', function () {
    $target = Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active']);
    $companion = Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active']);
    $rareCompanion = Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active']);
    $cancelledCompanion = Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active']);

    foreach ([OrderStatus::Paid, OrderStatus::Delivered] as $status) {
        $order = Order::factory()->status($status)->create();
        OrderDetail::factory()->create(['order_id' => $order->id, 'product_id' => $target->id]);
        OrderDetail::factory()->create(['order_id' => $order->id, 'product_id' => $companion->id]);
    }

    $order = Order::factory()->status(OrderStatus::Paid)->create();
    OrderDetail::factory()->create(['order_id' => $order->id, 'product_id' => $target->id]);
    OrderDetail::factory()->create(['order_id' => $order->id, 'product_id' => $rareCompanion->id]);

    $cancelled = Order::factory()->status(OrderStatus::Cancelled)->create();
    OrderDetail::factory()->create(['order_id' => $cancelled->id, 'product_id' => $target->id]);
    OrderDetail::factory()->create(['order_id' => $cancelled->id, 'product_id' => $cancelledCompanion->id]);

    $ids = app(FrontendProductService::class)->frequentlyBoughtWith($target->fresh(), 4)->pluck('id');

    expect($ids->first())->toBe($companion->id)
        ->and($ids)->toContain($rareCompanion->id)
        ->and($ids)->not->toContain($cancelledCompanion->id)
        ->and($ids)->not->toContain($target->id);
});

it('falls back to same-category products when there is no purchase history', function () {
    $target = Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active']);
    $sibling = Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active']);

    $ids = app(FrontendProductService::class)->relatedProducts($target->fresh(), 4)->pluck('id');

    expect($ids)->toContain($sibling->id)
        ->and($ids)->not->toContain($target->id);
});

// -- Responsive image thumbnails ------------------------------------------------

it('generates and serves a WebP thumbnail for a stored image', function () {
    File::ensureDirectoryExists(public_path('uploads/products'));
    $name = ImageManager::upload(UploadedFile::fake()->image('thumb-source.jpg', 1000, 1000), 'products');

    expect(ImageManager::thumbnailUrl($name, 'products'))->toBeNull();

    $thumbName = ImageManager::generateThumbnail($name, 'products', 480, 480);

    expect($thumbName)->not->toBeNull()
        ->and(ImageManager::thumbnailUrl($name, 'products'))->toContain('.webp');

    File::delete(public_path('uploads/products/'.$name));
    File::delete(public_path(ImageManager::path($thumbName, 'products')));
});

it('generates a responsive preview thumbnail when a product image is uploaded', function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.products.store'), [
            'category_id' => $this->category->id,
            'name' => ['en' => 'Responsive Image Product'],
            'product_type' => 'single',
            'price' => 20,
            'stock' => 5,
            'status' => 'active',
            'thumbnail' => UploadedFile::fake()->image('product-thumb.jpg', 1000, 1000),
        ])
        ->assertRedirect();

    $product = Product::latest('id')->firstOrFail();

    expect($product->thumbnail)->not->toBeNull()
        ->and($product->thumbnail_preview_url)->not->toBeNull();

    File::delete(public_path('uploads/products/'.$product->thumbnail));
    File::delete(public_path(ImageManager::path('thumbs/'.pathinfo($product->thumbnail, PATHINFO_FILENAME).'.webp', 'products')));
});

it('renders a responsive srcset on the PDP main image once a thumbnail exists', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active', 'thumbnail' => null]);

    File::ensureDirectoryExists(public_path('uploads/products'));
    $name = ImageManager::upload(UploadedFile::fake()->image('pdp-src.jpg', 1000, 1000), 'products');
    ImageManager::generateThumbnail($name, 'products', 480, 480);
    $product->update(['thumbnail' => $name]);

    $response = $this->get(route('frontend.shop.show', $product->slug));

    $response->assertOk();
    expect($response->getContent())->toContain('srcset="');

    File::delete(public_path('uploads/products/'.$name));
    File::delete(public_path(ImageManager::path('thumbs/'.pathinfo($name, PATHINFO_FILENAME).'.webp', 'products')));
});

it('still generates a MediaAsset thumbnail after the optimizer was refactored to share ImageManager', function () {
    $created = app(MediaAssetService::class)->store(
        [UploadedFile::fake()->image('media-thumb.jpg', 1000, 1000)],
        'media',
        null,
        null,
    );
    $asset = $created->first();

    expect($asset->thumbnail_filename)->not->toBeNull();

    File::delete(public_path(ImageManager::path($asset->filename, 'media')));
    File::delete(public_path(ImageManager::path($asset->thumbnail_filename, 'media')));
});
