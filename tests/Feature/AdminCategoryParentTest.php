<?php

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

function makeCategory(string $name, ?int $parentId = null): Category
{
    return Category::create([
        'name' => ['en' => $name],
        'slug' => str($name)->slug()->toString(),
        'parent_id' => $parentId,
        'status' => true,
        'sort_order' => 0,
    ]);
}

function categoryPayload(string $name, array $extra = []): array
{
    return ['name' => ['en' => $name], 'status' => '1', 'sort_order' => 0] + $extra;
}

it('shows a parent category picker on the create form', function () {
    $parent = makeCategory('Clothing');

    $this->actingAs($this->admin)
        ->get(route('admin.categories.create'))
        ->assertOk()
        ->assertSee('Parent Category')
        ->assertSee('Clothing');
});

it('creates a category under a parent', function () {
    $parent = makeCategory('Clothing');

    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), categoryPayload('Shirts', ['parent_id' => $parent->id]))
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHasNoErrors();

    $child = Category::query()->where('slug', 'shirts')->firstOrFail();

    expect($child->parent_id)->toBe($parent->id)
        ->and($child->parent->name)->toBe('Clothing')
        ->and($parent->children()->pluck('id')->all())->toBe([$child->id]);
});

it('treats an empty parent as top-level', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), categoryPayload('Shoes', ['parent_id' => '']))
        ->assertSessionHasNoErrors();

    expect(Category::query()->where('slug', 'shoes')->value('parent_id'))->toBeNull();
});

it('rejects an unknown parent', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.categories.create'))
        ->post(route('admin.categories.store'), categoryPayload('Shoes', ['parent_id' => 9999]))
        ->assertSessionHasErrors('parent_id');
});

it('rejects making a category its own parent or its descendant\'s child', function () {
    $root = makeCategory('Clothing');
    $child = makeCategory('Shirts', $root->id);
    $grandchild = makeCategory('Polo', $child->id);

    $this->actingAs($this->admin)
        ->from(route('admin.categories.edit', $root->id))
        ->put(route('admin.categories.update', $root->id), categoryPayload('Clothing', ['parent_id' => $root->id]))
        ->assertSessionHasErrors('parent_id');

    $this->actingAs($this->admin)
        ->from(route('admin.categories.edit', $root->id))
        ->put(route('admin.categories.update', $root->id), categoryPayload('Clothing', ['parent_id' => $grandchild->id]))
        ->assertSessionHasErrors('parent_id');

    expect($root->fresh()->parent_id)->toBeNull();
});

it('excludes the category and its descendants from its own parent picker', function () {
    $root = makeCategory('Clothing');
    $child = makeCategory('Shirts', $root->id);
    $other = makeCategory('Shoes');

    $ids = collect(Category::treeOptions($root))->pluck('id')->all();

    expect($ids)->toBe([$other->id]);
});

it('refuses to delete a category that still has sub-categories', function () {
    $root = makeCategory('Clothing');
    makeCategory('Shirts', $root->id);

    $this->actingAs($this->admin)
        ->from(route('admin.categories.index'))
        ->delete(route('admin.categories.destroy', $root->id))
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHas('error');

    expect(Category::query()->whereKey($root->id)->exists())->toBeTrue();
});
