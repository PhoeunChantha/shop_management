<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');

    // ProductFactory picks a random existing category/brand.
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);
});

it('requires authentication to toggle the wishlist', function () {
    $product = Product::factory()->create();

    $this->post(route('frontend.account.wishlist.toggle'), ['product_id' => $product->id])
        ->assertRedirect(route('frontend.login'));
});

it('toggles a product on and off, returning the saved state', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->customer)
        ->postJson(route('frontend.account.wishlist.toggle'), ['product_id' => $product->id])
        ->assertOk()
        ->assertJson(['wished' => true, 'count' => 1]);

    expect($this->customer->wishlist()->whereKey($product->id)->exists())->toBeTrue();

    $this->actingAs($this->customer)
        ->postJson(route('frontend.account.wishlist.toggle'), ['product_id' => $product->id])
        ->assertOk()
        ->assertJson(['wished' => false, 'count' => 0]);

    expect($this->customer->wishlist()->count())->toBe(0);
});

it('merges guest wishlist ids on sync without removing existing ones', function () {
    [$a, $b, $c] = Product::factory()->count(3)->create()->all();

    // Already saved in the account.
    $this->customer->wishlist()->attach($a->id);

    $this->actingAs($this->customer)
        ->postJson(route('frontend.account.wishlist.sync'), ['ids' => [$b->id, $c->id]])
        ->assertOk()
        ->assertJson(['count' => 3]);

    expect($this->customer->wishlist()->pluck('products.id')->sort()->values()->all())
        ->toBe(collect([$a->id, $b->id, $c->id])->sort()->values()->all());
});

it('rejects a non-existent product', function () {
    // The app renders validation failures as JSON only for api/* routes
    // (bootstrap/app.php shouldRenderJsonWhen), so this redirects with errors.
    $this->actingAs($this->customer)
        ->post(route('frontend.account.wishlist.toggle'), ['product_id' => 999999])
        ->assertInvalid('product_id');

    expect($this->customer->wishlist()->count())->toBe(0);
});
