<?php

use App\Models\AdminNotification;
use App\Models\Category;
use App\Models\Product;
use App\Services\Admin\AdminNotificationService;

it('stores generated notification links as host-less paths', function () {
    $category = Category::create(['name' => 'Tees', 'slug' => 'tees']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'status' => 'active',
        'stock' => 0,
    ]);

    app(AdminNotificationService::class)->refreshGenerated();

    $notification = AdminNotification::where('fingerprint', "out_of_stock:{$product->id}")->first();

    expect($notification)->not->toBeNull()
        ->and($notification->url)->toBe("/admin/inventory/{$product->id}");
});
