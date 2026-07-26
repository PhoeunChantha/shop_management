<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Admin\SettingService;

beforeEach(function () {
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);
});

it('shows real product specifications on the detail page', function () {
    $product = Product::factory()->create(['status' => 'active']);
    $product->specifications()->create(['name' => 'Material', 'value' => '100% organic cotton', 'sort_order' => 1]);
    $product->specifications()->create(['name' => 'Weight', 'value' => '240gsm', 'sort_order' => 2]);

    $this->get(route('frontend.shop.show', $product->slug))
        ->assertOk()
        ->assertSee('Material')
        ->assertSee('100% organic cotton')
        ->assertSee('Weight')
        ->assertSee('240gsm');
});

it('reads shipping info from settings with a fallback', function () {
    expect(app(SettingService::class)->shippingInfo())->toContain('Free standard shipping');

    Setting::set('shipping_returns_info', 'Ships within 24 hours.', 'general');

    expect(app(SettingService::class)->shippingInfo())->toBe('Ships within 24 hours.');
});
