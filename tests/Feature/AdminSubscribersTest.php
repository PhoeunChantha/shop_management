<?php

use App\Models\NewsletterSubscriber;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('lists subscribers for an admin', function () {
    NewsletterSubscriber::create(['email' => 'fan@example.com', 'subscribed_at' => now()]);

    $this->actingAs($this->admin)
        ->get(route('admin.subscribers.index'))
        ->assertOk()
        ->assertSee('fan@example.com');
});

it('forbids a customer without the permission', function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)
        ->get(route('admin.subscribers.index'))
        ->assertForbidden();
});

it('deletes a subscriber', function () {
    $sub = NewsletterSubscriber::create(['email' => 'x@example.com', 'subscribed_at' => now()]);

    $this->actingAs($this->admin)
        ->delete(route('admin.subscribers.destroy', $sub))
        ->assertRedirect();

    expect(NewsletterSubscriber::find($sub->id))->toBeNull();
});

it('exports subscribers as CSV', function () {
    NewsletterSubscriber::create(['email' => 'csv@example.com', 'subscribed_at' => now()]);

    $response = $this->actingAs($this->admin)->get(route('admin.subscribers.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});
