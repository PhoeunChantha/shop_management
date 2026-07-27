<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

it('prefills the checkout shipping form from the default address', function () {
    $user = User::factory()->create(['name' => 'Alex Rivera', 'email' => 'alex@example.com']);
    $user->assignRole('customer');
    $user->addresses()->create([
        'name' => 'Alex Rivera',
        'street' => '123 Market St',
        'city' => 'San Francisco',
        'zip' => '94103',
        'is_default' => true,
    ]);

    $this->actingAs($user)
        ->get(route('frontend.checkout.index'))
        ->assertOk()
        ->assertSee('value="alex@example.com"', false)
        ->assertSee('value="123 Market St"', false)
        ->assertSee('value="San Francisco"', false)
        ->assertSee('value="94103"', false)
        ->assertSee('value="Alex"', false)
        ->assertSee('value="Rivera"', false);
});

it('leaves the checkout form empty for guests', function () {
    $this->get(route('frontend.checkout.index'))
        ->assertOk()
        ->assertDontSee('value="alex@example.com"', false);
});
