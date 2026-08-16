<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @return array<int, string>
     */
    private function names(): array
    {
        return collect(['view', 'create', 'edit', 'delete'])
            ->map(fn (string $action): string => "{$action} purchasing reports")
            ->all();
    }

    public function up(): void
    {
        foreach ($this->names() as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                ['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            );
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->where('guard_name', 'web')->value('id');
        if ($adminRoleId) {
            $permissionIds = DB::table('permissions')->whereIn('name', $this->names())->pluck('id');
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
        $permissionIds = DB::table('permissions')->whereIn('name', $this->names())->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
