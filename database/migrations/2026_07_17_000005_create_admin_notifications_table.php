<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('fingerprint')->unique();
            $table->string('type', 50)->index();
            $table->enum('priority', ['info', 'warning', 'critical'])->default('info')->index();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->string('source_type', 80)->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['read_at', 'priority']);
            $table->index(['type', 'source_type', 'source_id']);
        });

        $permissions = collect(['view', 'create', 'edit', 'delete'])
            ->map(fn (string $action): array => [
                'name' => "{$action} notifications",
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                $permission,
            );
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->where('guard_name', 'web')->value('id');

        if ($adminRoleId) {
            $permissionIds = DB::table('permissions')
                ->whereIn('name', $permissions->pluck('name')->all())
                ->pluck('id');

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
        Schema::dropIfExists('admin_notifications');

        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['view notifications', 'create notifications', 'edit notifications', 'delete notifications'])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
