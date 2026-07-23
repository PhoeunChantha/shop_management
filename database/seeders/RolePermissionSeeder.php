<?php

namespace Database\Seeders;

use App\Models\User;
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
            'colors', 'coupons', 'deals', 'returns', 'orders', 'banners', 'collections', 'announcements',
            'shipping', 'taxes', 'pages', 'faqs', 'reviews', 'suppliers', 'purchase orders', 'abandoned carts', 'reports', 'saved views', 'users', 'settings', 'notifications', 'seo', 'role', 'permission',
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
        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        // Admin gets every admin-panel permission.
        $adminRole->syncPermissions(Permission::all());

        // The 'customer' role is for storefront shoppers — no admin-panel permissions.
        $customerRole->syncPermissions([]);

        // Migrate any legacy 'user' role (the former customer role) to 'customer',
        // move its members over, then retire it so customers live under one role.
        $legacyRole = Role::where('name', 'user')->where('guard_name', 'web')->first();

        if ($legacyRole) {
            User::role($legacyRole)->get()->each(function (User $user) use ($customerRole): void {
                $user->assignRole($customerRole);
                $user->removeRole('user');
            });

            $legacyRole->delete();
        }
    }
}
