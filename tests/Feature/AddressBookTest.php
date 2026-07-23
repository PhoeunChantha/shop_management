<?php

use App\Models\Address;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
});

it('creates the first address as default', function () {
    $this->actingAs($this->customer)
        ->post(route('frontend.account.addresses.store'), [
            'name' => 'Alex Rivera', 'street' => '123 Market St', 'city' => 'SF', 'zip' => '94103',
        ])->assertRedirect();

    $address = $this->customer->addresses()->first();
    expect($address->name)->toBe('Alex Rivera')
        ->and($address->is_default)->toBeTrue();
});

it('keeps exactly one default when a new default is added', function () {
    $first = $this->customer->addresses()->create(['name' => 'A', 'street' => 'One', 'is_default' => true]);

    $this->actingAs($this->customer)
        ->post(route('frontend.account.addresses.store'), [
            'name' => 'B', 'street' => 'Two', 'is_default' => '1',
        ])->assertRedirect();

    $defaults = $this->customer->addresses()->where('is_default', true)->pluck('name');
    expect($defaults)->toHaveCount(1)->and($defaults->first())->toBe('B');
    expect($first->refresh()->is_default)->toBeFalse();
});

it('promotes another address to default after deleting the default', function () {
    $default = $this->customer->addresses()->create(['name' => 'A', 'street' => 'One', 'is_default' => true]);
    $other = $this->customer->addresses()->create(['name' => 'B', 'street' => 'Two', 'is_default' => false]);

    $this->actingAs($this->customer)
        ->delete(route('frontend.account.addresses.destroy', $default))
        ->assertRedirect();

    expect(Address::find($default->id))->toBeNull()
        ->and($other->refresh()->is_default)->toBeTrue();
});

it('sets a chosen address as default', function () {
    $this->customer->addresses()->create(['name' => 'A', 'street' => 'One', 'is_default' => true]);
    $other = $this->customer->addresses()->create(['name' => 'B', 'street' => 'Two', 'is_default' => false]);

    $this->actingAs($this->customer)
        ->patch(route('frontend.account.addresses.default', $other))
        ->assertRedirect();

    expect($other->refresh()->is_default)->toBeTrue()
        ->and($this->customer->addresses()->where('is_default', true)->count())->toBe(1);
});

it('forbids acting on another customer address', function () {
    $stranger = User::factory()->create();
    $stranger->assignRole('customer');
    $theirs = $stranger->addresses()->create(['name' => 'X', 'street' => 'Nope', 'is_default' => true]);

    $this->actingAs($this->customer)
        ->delete(route('frontend.account.addresses.destroy', $theirs))
        ->assertForbidden();

    expect(Address::find($theirs->id))->not->toBeNull();
});
