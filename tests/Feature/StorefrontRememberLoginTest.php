<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Auth;

it('ticks "Keep me signed in" on the login page by default', function () {
    $this->get(route('frontend.login'))
        ->assertOk()
        ->assertSee('name="remember" value="1" checked', false);
});

it('keeps the customer signed in after the session expires when remembered', function () {
    $user = User::factory()->create(['password' => 'secret-pass-123']);

    $response = $this->post(route('frontend.login.store'), [
        'email' => $user->email, 'password' => 'secret-pass-123', 'remember' => '1',
    ]);

    $response->assertCookie(Auth::guard()->getRecallerName());
    expect($user->fresh()->remember_token)->not->toBeNull();
});

it('remembers a newly registered customer', function () {
    $this->seed(RolePermissionSeeder::class);

    $this->post(route('frontend.register.store'), [
        'first_name' => 'Sok', 'last_name' => 'Dara', 'email' => 'dara@example.com', 'password' => 'Str0ng-Passw0rd!',
    ])->assertRedirect();

    expect(User::where('email', 'dara@example.com')->value('remember_token'))->not->toBeNull();
});
