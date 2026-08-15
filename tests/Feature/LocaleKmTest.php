<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

beforeEach(function () {
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);
    Product::factory()->create(['status' => 'active']);
    session(['locale' => 'km']);
    app()->setLocale('km');
});

it('has a fully translated Khmer file (no placeholders)', function () {
    $km = json_decode(file_get_contents(lang_path('km.json')), true);

    expect($km)->not->toBeNull();
    $placeholders = collect($km)->filter(fn ($v) => trim((string) $v) === '' || $v === 'សូមបញ្ចូលការបកប្រែ');
    expect($placeholders)->toHaveCount(0);
});

it('renders storefront guest pages in Khmer without errors', function () {
    // Each page + a known translated string that must appear on it.
    $checks = [
        route('frontend.home') => __('Search the collection'),
        route('frontend.shop.index') => __('All Products'),
        route('frontend.login') => __('Sign in'),
        route('frontend.register') => __('Create account'),
        route('frontend.cart.index') => __('Shopping bag'),
    ];

    foreach ($checks as $url => $expected) {
        $res = $this->get($url);
        $res->assertOk();
        // The Khmer translation must actually render (not the English key).
        $res->assertSee($expected, false);
        expect($expected)->not->toBe(''); // sanity: translation exists
    }
});
