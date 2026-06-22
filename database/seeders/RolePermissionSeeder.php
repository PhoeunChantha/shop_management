<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        // Create permissions
        $permissions = [
            'view role',
            'edit role',
            'create role',
            'delete role',
            'view users',
            'edit users',
            'create users',
            'delete users',
            'view permission',
            'edit permission',
            'create permission',
            'delete permission',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        // Assign all permissions to admin role
        $adminRole->syncPermissions(Permission::all());

        // Assign limited permissions to user role
        $userRole->syncPermissions([
            'view role',
            'view users',
            'view permission',
        ]);
    }
}
