<?php

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

$pages = ['index', 'sales', 'products', 'stock', 'payments', 'customers', 'purchasing', 'register', 'returns'];

it('renders every report page for an admin', function (string $page) {
    $this->actingAs($this->admin)
        ->get(route("admin.reports.{$page}"))
        ->assertOk();
})->with($pages);

$tablePages = ['sales', 'products', 'stock', 'payments', 'customers', 'purchasing', 'register', 'returns'];

it('renders report tables with search, per-page and pagination params', function (string $page) {
    $this->actingAs($this->admin)
        ->get(route("admin.reports.{$page}", ['search' => 'abc', 'per_page' => 5, 'page' => 2]))
        ->assertOk();
})->with($tablePages);

$exports = ['sales.export', 'products.export', 'stock.export', 'payments.export', 'customers.export', 'purchasing.export', 'register.export', 'returns.export'];

it('streams every report export as CSV for an admin', function (string $route) {
    $response = $this->actingAs($this->admin)->get(route("admin.reports.{$route}"));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
})->with($exports);

it('streams every report export as PDF for an admin', function (string $route) {
    $response = $this->actingAs($this->admin)->get(route("admin.reports.{$route}", ['format' => 'pdf']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
})->with($exports);

it('forbids a customer from viewing report pages', function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)
        ->get(route('admin.reports.sales'))
        ->assertForbidden();
});
