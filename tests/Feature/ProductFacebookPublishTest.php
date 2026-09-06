<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Admin\EnvService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');

    $this->category = Category::create(['name' => 'Tees', 'slug' => 'tees']);

    // Point EnvService at a scratch .env so the real one is never touched.
    $this->envPath = storage_path('framework/testing-env-'.uniqid().'.env');
    file_put_contents($this->envPath, "APP_NAME=Shop\n");
    app()->loadEnvironmentFrom(basename($this->envPath));
    app()->useEnvironmentPath(dirname($this->envPath));

    $this->imageDir = public_path('uploads/products');
    File::ensureDirectoryExists($this->imageDir);
    $this->imagePath = $this->imageDir.'/facebook-test.jpg';
    file_put_contents($this->imagePath, 'fake-image-bytes');
});

afterEach(function () {
    @unlink($this->envPath);
    @unlink($this->imagePath);
});

function enableFacebookPublishing(): void
{
    Setting::set('facebook_publish_enabled', '1', 'facebook');
    Setting::set('facebook_page_id', '1234567890', 'facebook');
    app(EnvService::class)->set(['FACEBOOK_PAGE_ACCESS_TOKEN' => 'test-token']);
}

it('denies publishing to a user without product edit permission', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'thumbnail' => 'facebook-test.jpg']);

    $this->actingAs($this->customer)
        ->post(route('admin.products.publish-facebook', $product->id))
        ->assertForbidden();
});

it('refuses to publish when Facebook is not configured', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'thumbnail' => 'facebook-test.jpg']);

    $this->actingAs($this->admin)
        ->post(route('admin.products.publish-facebook', $product->id))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(session('error'))->toContain('not set up');
    Http::assertNothingSent();
});

it('refuses to publish a product with no image', function () {
    enableFacebookPublishing();
    $product = Product::factory()->create(['category_id' => $this->category->id, 'thumbnail' => null]);

    $this->actingAs($this->admin)
        ->post(route('admin.products.publish-facebook', $product->id))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(session('error'))->toContain('no image');
    Http::assertNothingSent();
});

it('publishes a product photo to the Facebook page', function () {
    enableFacebookPublishing();
    Http::fake(['graph.facebook.com/*' => Http::response(['id' => '999_888', 'post_id' => '1234567890_999888'], 200)]);

    $product = Product::factory()->create(['category_id' => $this->category->id, 'thumbnail' => 'facebook-test.jpg']);

    $this->actingAs($this->admin)
        ->post(route('admin.products.publish-facebook', $product->id))
        ->assertRedirect()
        ->assertSessionHas('success');

    $product->refresh();
    expect($product->facebook_post_id)->toBe('1234567890_999888')
        ->and($product->facebook_permalink_url)->toBe('https://www.facebook.com/1234567890_999888')
        ->and($product->facebook_posted_at)->not->toBeNull()
        ->and($product->facebook_published)->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/1234567890/photos'));
});

it('surfaces a readable error when Facebook rejects the request', function () {
    enableFacebookPublishing();
    Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token.']], 400)]);

    $product = Product::factory()->create(['category_id' => $this->category->id, 'thumbnail' => 'facebook-test.jpg']);

    $this->actingAs($this->admin)
        ->post(route('admin.products.publish-facebook', $product->id))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(session('error'))->toContain('Invalid OAuth access token.');
    expect($product->refresh()->facebook_post_id)->toBeNull();
});
