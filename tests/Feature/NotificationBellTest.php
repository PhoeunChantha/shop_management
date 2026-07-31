<?php

use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

it('shows the notification bell with a badge for a logged-in customer', function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('customer');

    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\OrderStatusUpdated',
        'data' => ['title' => 'Order shipped'],
    ]);

    $this->actingAs($user)
        ->get(route('frontend.home'))
        ->assertOk()
        ->assertSee('data-notif-count', false);
});

it('hides the notification bell from guests', function () {
    $this->get(route('frontend.home'))
        ->assertOk()
        ->assertDontSee('data-notif-count', false);
});
