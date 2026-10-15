<?php

use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Services\Frontend\NavigationService;

beforeEach(function () {
    $this->category = Category::create(['name' => 'Tees', 'slug' => 'tees']);
});

it('shows popular products in the search popup with their photo and the shopper currency', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id, 'status' => 'active', 'is_best_seller' => true, 'thumbnail' => 'tee.webp',
        'price' => 20, 'discount_type' => null, 'discount_amount' => 0,
    ]);
    app(NavigationService::class)->flush();

    $html = $this->get(route('frontend.home'))->assertOk()->getContent();
    $popular = str($html)->after('ut-search-products')->before('</div>');

    expect((string) $popular)
        ->toContain('<img class="ut-search-product-image" src="'.$product->thumbnail_url.'"')
        ->toContain(money(20));

    // The list is cached for everyone; the price still follows each shopper's currency.
    Currency::create(['code' => 'KHR', 'name' => 'Riel', 'symbol' => '៛', 'symbol_position' => 'after', 'exchange_rate' => 4000, 'decimals' => 0, 'status' => true]);

    $html = $this->withSession(['display_currency' => 'KHR'])->get(route('frontend.home'))->getContent();

    expect((string) str($html)->after('ut-search-products')->before('</div>'))->toContain('80,000៛');
});

it('falls back to the placeholder tile when a product has no image', function () {
    Product::factory()->create(['category_id' => $this->category->id, 'status' => 'active', 'thumbnail' => null]);
    app(NavigationService::class)->flush();

    $html = $this->get(route('frontend.home'))->assertOk()->getContent();

    expect((string) str($html)->after('ut-search-products')->before('</div>'))->toContain('<i class="ut-search-product-image');
});
