<?php

use App\Models\Category;
use App\Models\Product;

it('shows sub-categories in the mobile menu', function () {
    $parent = Category::create(['name' => 'T-Shirts', 'slug' => 't-shirts', 'status' => true]);
    $child = Category::create(['name' => 'Oversized Tees', 'slug' => 'oversized-tees', 'parent_id' => $parent->id, 'status' => true]);
    Product::factory()->create(['category_id' => $parent->id, 'sub_category_id' => $child->id, 'status' => 'active']);

    $html = $this->get(route('frontend.home'))->assertOk()->getContent();
    $mobile = substr($html, strpos($html, 'id="utMobileMenu"'));

    expect($mobile)->toContain('<details class="ut-mnav-group">')
        ->toContain('T-Shirts')
        ->toContain('Shop all T-Shirts')
        ->toContain('Oversized Tees');
});
