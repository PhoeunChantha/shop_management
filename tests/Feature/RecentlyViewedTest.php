<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);

    $this->a = Product::factory()->create(['status' => 'active']);
    $this->b = Product::factory()->create(['status' => 'active']);
});

it('shows the recently-viewed rail only after viewing a product (guest, session)', function () {
    // First product: nothing viewed yet.
    $this->get(route('frontend.shop.show', $this->a->slug))
        ->assertOk()
        ->assertDontSee('Recently viewed');

    // Second product: the first now appears under "Recently viewed".
    $this->get(route('frontend.shop.show', $this->b->slug))
        ->assertOk()
        ->assertSee('Recently viewed');
});

it('persists recently viewed to the database for logged-in customers', function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->actingAs($user)->get(route('frontend.shop.show', $this->a->slug))->assertOk();
    $this->actingAs($user)->get(route('frontend.shop.show', $this->b->slug))
        ->assertOk()
        ->assertSee('Recently viewed');

    expect(DB::table('recently_viewed')->where('user_id', $user->id)->count())->toBe(2);
});
