<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

beforeEach(function () {
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);
});

it('serves an XML sitemap with static pages and active products', function () {
    $product = Product::factory()->create(['status' => 'active', 'slug' => 'heavy-tee']);
    Product::factory()->create(['status' => 'inactive', 'slug' => 'hidden-tee']);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/xml');
    $response->assertSee('<urlset', false)
        ->assertSee(route('frontend.shop.index'), false)
        ->assertSee(route('frontend.shop.show', $product->slug), false)
        ->assertDontSee(route('frontend.shop.show', 'hidden-tee'), false);
});
