<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

beforeEach(function () {
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);
});

it('renders availability toggles and best-seller data on the shop page', function () {
    Product::factory()->create(['status' => 'active', 'is_best_seller' => true]);

    $this->get(route('frontend.shop.index'))
        ->assertOk()
        ->assertSee('New arrivals')
        ->assertSee('Best sellers')
        ->assertSee('data-best="1"', false);
});
