<?php

use Database\Seeders\RolePermissionSeeder;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $this->seed(RolePermissionSeeder::class);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    // A self-registered account is a customer, not an admin — it lands on
    // the storefront account area, never the admin dashboard.
    $response->assertRedirect(route('frontend.account.dashboard', absolute: false));
    expect($this->app['auth']->user()->hasRole('customer'))->toBeTrue();
});
