<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

beforeEach(function () {
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);
});

it('renders product-specific SEO/social meta on a product page', function () {
    $product = Product::factory()->create(['status' => 'active']);

    $this->get(route('frontend.shop.show', $product->slug))
        ->assertOk()
        ->assertSee('property="og:type" content="product"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:url"', false)
        ->assertSee('name="twitter:card"', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('name="description"', false);
});

it('renders default store SEO meta on the home page', function () {
    $this->get(route('frontend.home'))
        ->assertOk()
        ->assertSee('property="og:site_name"', false)
        ->assertSee('property="og:type" content="website"', false)
        ->assertSee('rel="canonical"', false);
});
