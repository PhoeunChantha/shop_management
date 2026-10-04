<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $role = Role::create(['name' => 'merch', 'guard_name' => 'web']);
    $role->givePermissionTo(['view product reports', 'view stock reports']);
    $this->user = User::factory()->create()->assignRole($role);
    $this->admin = User::factory()->create()->assignRole('admin');
});

it('hides product cost, profit and margin without the finance permission', function () {
    $this->actingAs($this->user)->get(route('admin.reports.products'))
        ->assertOk()->assertDontSee('COGS')->assertDontSee('Gross profit');

    $csv = $this->actingAs($this->user)->get(route('admin.reports.products.export'))->streamedContent();
    expect(strtok($csv, "\n"))->toBe('Product,SKU,Quantity,Revenue,Stock');

    $this->actingAs($this->admin)->get(route('admin.reports.products'))->assertOk()->assertSee('COGS');
});

it('hides stock unit cost and value without the finance permission', function () {
    $this->actingAs($this->user)->get(route('admin.reports.stock'))
        ->assertOk()->assertDontSee('Unit cost')->assertDontSee('Stock value');

    $csv = $this->actingAs($this->user)->get(route('admin.reports.stock.export'))->streamedContent();
    expect(strtok($csv, "\n"))->not->toContain('Unit Cost');

    $this->actingAs($this->admin)->get(route('admin.reports.stock'))->assertOk()->assertSee('Unit cost');
});

it('ignores a cost sort without the finance permission', function () {
    $this->actingAs($this->user)->get(route('admin.reports.products', ['sort' => 'profit']))->assertOk();
});
