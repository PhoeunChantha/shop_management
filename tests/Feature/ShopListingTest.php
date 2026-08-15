<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

beforeEach(function () {
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);
});

/** Product names in the paginated listing (server-side result, not the header search dropdown). */
function listedNames($response): Collection
{
    return collect($response->viewData('products')->items())->pluck('name');
}

it('renders availability toggles and active products on the shop page', function () {
    Product::factory()->create([
        'status' => 'active',
        'is_best_seller' => true,
        'name' => 'Signature Heavyweight Tee',
    ]);

    $this->get(route('frontend.shop.index'))
        ->assertOk()
        ->assertSee('New arrivals')
        ->assertSee('Best sellers')
        ->assertSee('Signature Heavyweight Tee');
});

it('filters to best sellers server-side via the best flag', function () {
    Product::factory()->create(['status' => 'active', 'is_best_seller' => true, 'name' => 'Bestseller Alpha']);
    Product::factory()->create(['status' => 'active', 'is_best_seller' => false, 'name' => 'Ordinary Beta']);

    $response = $this->get(route('frontend.shop.index', ['best' => 1]))->assertOk();
    $names = listedNames($response);

    expect($names)->toContain('Bestseller Alpha')
        ->and($names)->not->toContain('Ordinary Beta');
});

it('searches products by name server-side', function () {
    Product::factory()->create(['status' => 'active', 'name' => 'Midnight Hoodie']);
    Product::factory()->create(['status' => 'active', 'name' => 'Sunrise Shorts']);

    $response = $this->get(route('frontend.shop.index', ['q' => 'Midnight']))->assertOk();
    $names = listedNames($response);

    expect($names)->toContain('Midnight Hoodie')
        ->and($names)->not->toContain('Sunrise Shorts');
});

it('excludes non-active products from the listing', function () {
    Product::factory()->create(['status' => 'active', 'name' => 'Published Piece']);
    Product::factory()->create(['status' => 'draft', 'name' => 'Hidden Draft']);

    $response = $this->get(route('frontend.shop.index'))->assertOk();
    $names = listedNames($response);

    expect($names)->toContain('Published Piece')
        ->and($names)->not->toContain('Hidden Draft');
});

it('paginates the listing at 24 products per page', function () {
    Product::factory()->count(30)->create(['status' => 'active']);

    $response = $this->get(route('frontend.shop.index'))->assertOk();
    $paginator = $response->viewData('products');

    expect($paginator->perPage())->toBe(24)
        ->and($paginator->total())->toBe(30)
        ->and($paginator->count())->toBe(24)
        ->and($paginator->hasPages())->toBeTrue();
});
