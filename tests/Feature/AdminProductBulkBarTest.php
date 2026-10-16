<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('renders the product bulk bar as dropdown menus', function () {
    Category::create(['name' => ['en' => 'Hoodies'], 'slug' => 'hoodies', 'status' => true]);
    Brand::create(['name' => 'Mono', 'slug' => 'mono', 'status' => true]);

    $this->actingAs($this->admin)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertSee('bulk-menu__trigger', false)
        ->assertSee('data-label="Hoodies"', false)
        ->assertSee('data-label="Mono"', false)
        ->assertDontSee('class="bulk-select', false);
});

it('applies a flag picked from the bulk menu', function () {
    $category = Category::create(['name' => ['en' => 'Tees'], 'slug' => 'tees', 'status' => true]);
    $product = Product::factory()->create(['category_id' => $category->id, 'is_featured' => false]);

    $this->actingAs($this->admin)
        ->patch(route('admin.products.bulk-update'), [
            'ids' => [$product->id],
            'operation' => 'flag',
            'flag' => 'is_featured',
            'flag_value' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($product->fresh()->is_featured)->toBeTruthy();
});
