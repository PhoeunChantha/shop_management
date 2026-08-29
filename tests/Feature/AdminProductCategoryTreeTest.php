<?php

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

    $this->root = Category::create(['name' => ['en' => 'Clothing'], 'slug' => 'clothing', 'status' => true]);
    $this->child = Category::create(['name' => ['en' => 'Shirts'], 'slug' => 'shirts', 'parent_id' => $this->root->id, 'status' => true]);
    $this->grandchild = Category::create(['name' => ['en' => 'Polo'], 'slug' => 'polo', 'parent_id' => $this->child->id, 'status' => true]);
});

function productPayload(int $categoryId, string $name = 'Tree Tee'): array
{
    return [
        'category_id' => $categoryId,
        'name' => ['en' => $name],
        'product_type' => 'single',
        'price' => 25,
        'stock' => 10,
        'status' => 'active',
    ];
}

it('lists sub-categories under their parent in the product form picker', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.products.create'))
        ->assertOk()
        ->assertSeeInOrder(['Clothing', '- Shirts', '-- Polo'])
        ->assertDontSee('name="sub_category_id"', false);
});

it('files a product under a sub-category and sets its main category automatically', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), productPayload($this->child->id))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $product = Product::query()->where('name->en', 'Tree Tee')->firstOrFail();

    expect($product->category_id)->toBe($this->root->id)
        ->and($product->sub_category_id)->toBe($this->child->id);
});

it('uses the top-level ancestor as main category for deeper nesting', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), productPayload($this->grandchild->id))
        ->assertSessionHasNoErrors();

    $product = Product::query()->where('name->en', 'Tree Tee')->firstOrFail();

    expect($product->category_id)->toBe($this->root->id)
        ->and($product->sub_category_id)->toBe($this->grandchild->id);
});

it('clears the sub-category when a top-level category is chosen on update', function () {
    $product = Product::factory()->create([
        'category_id' => $this->root->id,
        'sub_category_id' => $this->child->id,
        'product_type' => 'single',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.products.update', $product->id), productPayload($this->root->id, 'Renamed Tee'))
        ->assertSessionHasNoErrors();

    $product->refresh();

    expect($product->category_id)->toBe($this->root->id)
        ->and($product->sub_category_id)->toBeNull();
});

it('pre-selects the sub-category when editing a nested product', function () {
    $product = Product::factory()->create([
        'category_id' => $this->root->id,
        'sub_category_id' => $this->child->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.products.edit', $product->id))
        ->assertOk()
        ->assertSee('value="'.$this->child->id.'" selected', false);
});
