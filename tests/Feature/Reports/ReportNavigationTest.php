<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('seeds the new report domain permissions and grants them to admin', function () {
    $admin = User::factory()->create()->assignRole('admin');

    foreach (['view order reports', 'view marketing reports', 'view finance reports'] as $permission) {
        expect($admin->can($permission))->toBeTrue();
    }
});

it('shows business-domain names in the reports sidebar', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.reports.sales'))
        ->assertOk()
        ->assertSee('Payments &amp; Finance', false)
        ->assertSee('Returns &amp; Refunds', false)
        ->assertDontSee('Customer Registrations');
});

it('nests registrations as a tab inside the customers domain', function (string $page) {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route("admin.reports.{$page}"))
        ->assertOk()
        ->assertSee('report-tabs', false)
        ->assertSee(route('admin.reports.register'), false)
        ->assertSee(route('admin.reports.customers'), false);
})->with(['customers', 'register']);

it('hides the registrations tab without its permission', function () {
    $role = Role::create(['name' => 'analyst', 'guard_name' => 'web']);
    $role->givePermissionTo(['view dashboard', 'view customer reports']);
    $user = User::factory()->create()->assignRole($role);

    $this->actingAs($user)
        ->get(route('admin.reports.customers'))
        ->assertOk()
        ->assertDontSee('class="report-tabs"', false);
});

it('omits the reports group entirely for a user with no report permissions', function () {
    $role = Role::create(['name' => 'clerk', 'guard_name' => 'web']);
    $role->givePermissionTo(['view dashboard', 'view orders']);
    $user = User::factory()->create()->assignRole($role);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Analytics and exports');
});
