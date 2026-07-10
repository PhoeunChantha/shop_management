<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Granular resource permissions, matching the "{action} {subject}" convention
        // the policies check (e.g. AdminRolePolicy → "edit products").
        $subjects = [
            'products', 'brands', 'attributes', 'categories', 'sizes',
            'colors', 'coupons', 'orders', 'banners', 'users', 'settings', 'role', 'permission',
        ];
        $actions = ['view', 'create', 'edit', 'delete'];

        $permissions = [];
        foreach ($subjects as $subject) {
            foreach ($actions as $action) {
                $permissions[] = "{$action} {$subject}";
            }
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Admin gets every admin-panel permission.
        $adminRole->syncPermissions(Permission::all());

        // The 'user' role is for storefront customers — no admin-panel permissions.
        $userRole->syncPermissions([]);
    }
}
