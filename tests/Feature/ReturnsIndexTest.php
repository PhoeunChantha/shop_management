<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->admin = User::factory()->create()->assignRole('admin');
});

it('shows count-up stat cards and the status filters inline with the search', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.returns.index'))
        ->assertOk()
        ->assertSee('data-count-up', false)
        ->assertSeeInOrder(['toolbar-filters', 'name="status"', 'name="refund_status"', 'name="search"'], false)
        ->assertDontSee('return-filter-card', false)
        ->assertDontSee('toolbar-filters__clear', false);
});

it('offers a clear link once a filter is active', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.returns.index', ['status' => 'requested', 'per_page' => 25]))
        ->assertOk()
        ->assertSee('toolbar-filters__clear', false)
        ->assertSee(route('admin.returns.index', ['per_page' => 25]), false);
});
