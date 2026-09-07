<?php

use App\Exports\ProductsExport;
use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\User;
use App\Services\Admin\CommandPaletteService;
use App\Services\Admin\ProductService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // A staff account with no granular permissions at all — same shape as a
    // logged-in customer, used to prove the previously-missing gates now hold.
    $this->unprivileged = User::factory()->create();
    $this->unprivileged->assignRole('customer');

    $this->category = Category::create(['name' => 'Tees', 'slug' => 'tees']);
});

// -- Media library authorization -------------------------------------------------

it('denies the media library to a user without view media permission', function () {
    $this->actingAs($this->unprivileged)
        ->get(route('admin.media.index'))
        ->assertForbidden();
});

it('denies media upload to a user without create media permission', function () {
    $this->actingAs($this->unprivileged)
        ->post(route('admin.media.store'), [
            'folder' => 'media',
            'files' => [UploadedFile::fake()->image('logo.jpg')],
        ])
        ->assertForbidden();
});

it('denies media deletion to a user without delete media permission', function () {
    $media = MediaAsset::create(['folder' => 'media', 'filename' => 'a.jpg', 'original_name' => 'a.jpg']);

    $this->actingAs($this->unprivileged)
        ->delete(route('admin.media.destroy', $media))
        ->assertForbidden();
});

it('allows the media library for an admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.media.index'))
        ->assertOk();
});

// -- Activity log authorization ---------------------------------------------------

it('denies the activity log to a user without view activity logs permission', function () {
    $this->actingAs($this->unprivileged)
        ->get(route('admin.activity.index'))
        ->assertForbidden();

    $this->actingAs($this->unprivileged)
        ->get(route('admin.activity.export'))
        ->assertForbidden();
});

it('allows the activity log for an admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.activity.index'))
        ->assertOk();
});

// -- Command palette result filtering ---------------------------------------------

it('omits permission-gated result groups from the command palette for an unprivileged user', function () {
    Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Special Findable Shirt']);

    $groups = app(CommandPaletteService::class)->search('Special', $this->unprivileged);
    $labels = collect($groups)->keyBy('label');

    expect($labels['Products']['items'])->toBeEmpty()
        ->and($labels['Orders']['items'])->toBeEmpty()
        ->and($labels['Customers']['items'])->toBeEmpty()
        ->and($labels['Returns']['items'])->toBeEmpty()
        ->and($labels['Media']['items'])->toBeEmpty();
});

it('includes a result group in the command palette once the user holds the matching permission', function () {
    Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Special Findable Shirt']);
    $this->unprivileged->givePermissionTo('view products');

    $groups = app(CommandPaletteService::class)->search('Special', $this->unprivileged->fresh());
    $labels = collect($groups)->keyBy('label');

    expect(collect($labels['Products']['items'])->pluck('title'))->toContain('Special Findable Shirt');
});

// -- SVG upload rejection ----------------------------------------------------------

it('rejects an SVG file as a product thumbnail', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            'category_id' => $this->category->id,
            'name' => ['en' => 'SVG Test Product'],
            'product_type' => 'single',
            'price' => 10,
            'stock' => 1,
            'status' => 'active',
            'thumbnail' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors('thumbnail');
});

it('rejects an SVG file as a category image', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), [
            'name' => ['en' => 'SVG Category'],
            'status' => 1,
            'image' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors('image');
});

it('rejects an SVG file as a brand image', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.brands.store'), [
            'name' => 'SVG Brand',
            'status' => 1,
            'image' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors('image');
});

// -- JSON-LD output escaping --------------------------------------------------------

it('escapes a product name that attempts to break out of the JSON-LD script tag', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'name' => 'Evil</script><script>alert(1)</script>',
        'status' => 'active',
    ]);

    $response = $this->get(route('frontend.shop.show', $product->slug));

    $response->assertOk();
    expect($response->getContent())->not->toContain('</script><script>alert(1)</script>');
});

// -- CSV/formula injection in product export ----------------------------------------

it('escapes a leading-formula product name in the product export', function () {
    Product::factory()->create([
        'category_id' => $this->category->id,
        'name' => '=cmd|\'/c calc\'!A1',
        'sku' => 'FORMULA-1',
    ]);

    $export = new ProductsExport(
        app(ProductService::class),
        [],
        ['en'],
        'en',
    );

    $row = $export->query()->where('sku', 'FORMULA-1')->first();
    $mapped = $export->map($row);

    // The first translated name column follows [sku, ...names].
    expect($mapped[1])->toStartWith("'=");
});
