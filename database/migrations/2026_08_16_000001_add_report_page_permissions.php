<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $subjects = [
        'sales reports', 'product reports', 'stock reports', 'payment reports',
        'customer reports', 'register reports', 'return reports',
    ];

    public function up(): void
    {
        $names = [];
        foreach ($this->subjects as $subject) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $names[] = "{$action} {$subject}";
            }
        }

        foreach ($names as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                ['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            );
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->where('guard_name', 'web')->value('id');
        if ($adminRoleId) {
            $permissionIds = DB::table('permissions')->whereIn('name', $names)->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $adminRoleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $names = [];
        foreach ($this->subjects as $subject) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $names[] = "{$action} {$subject}";
            }
        }

        $permissionIds = DB::table('permissions')->whereIn('name', $names)->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
