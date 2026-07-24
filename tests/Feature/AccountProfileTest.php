<?php

use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $this->customer = User::factory()->create(['name' => 'Old Name', 'password' => Hash::make('secret123')]);
    $this->customer->assignRole('customer');
});

it('updates the display name and stores the phone on the customer profile', function () {
    $this->actingAs($this->customer)
        ->put(route('frontend.account.profile.update'), [
            'first_name' => 'Alex',
            'last_name' => 'Rivera',
            'phone' => '+855 12 345 678',
        ])->assertRedirect();

    expect($this->customer->refresh()->name)->toBe('Alex Rivera');
    expect(CustomerProfile::where('email', $this->customer->email)->value('phone'))->toBe('+855 12 345 678');
});

it('requires a first name', function () {
    $this->actingAs($this->customer)
        ->put(route('frontend.account.profile.update'), ['first_name' => '', 'last_name' => 'X'])
        ->assertInvalid('first_name');
});

it('changes the password with the correct current password', function () {
    $this->actingAs($this->customer)
        ->put(route('frontend.account.password.update'), [
            'current_password' => 'secret123',
            'password' => 'newsecret456',
            'password_confirmation' => 'newsecret456',
        ])->assertRedirect();

    expect(Hash::check('newsecret456', $this->customer->refresh()->password))->toBeTrue();
});

it('rejects a wrong current password', function () {
    $this->actingAs($this->customer)
        ->put(route('frontend.account.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'newsecret456',
            'password_confirmation' => 'newsecret456',
        ])->assertInvalid('current_password');

    expect(Hash::check('secret123', $this->customer->refresh()->password))->toBeTrue();
});

it('requires authentication', function () {
    $this->put(route('frontend.account.profile.update'), ['first_name' => 'X'])
        ->assertRedirect(route('frontend.login'));
});
