<?php

use App\Models\Order;
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

it('puts the sales export beside the tabs and swaps tabs in place', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.reports.sales'))
        ->assertOk()
        ->assertSeeInOrder(['report-tabs-bar', 'data-report-tabs', 'report-tabs-bar__actions', 'Export CSV'], false)
        ->assertSeeInOrder(['data-report-tabs', 'report-tabs__link is-active', 'data-ajax-link'], false);
});

it('keeps the chosen customer in the sales filter', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Order::factory()->create(['customer_name' => 'Dara Sok', 'customer_email' => 'dara@example.com']);

    $this->actingAs($admin)
        ->get(route('admin.reports.sales', ['customer' => 'dara@example.com']))
        ->assertOk()
        ->assertSee('report-controlbar', false)
        ->assertSee('name="customer" x-ref="value" value="dara@example.com"', false)
        ->assertSee('Dara Sok');
});
