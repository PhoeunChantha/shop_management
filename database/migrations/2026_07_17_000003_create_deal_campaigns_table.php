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
        Schema::create('deal_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('type', ['flash', 'daily', 'featured', 'clearance'])->index();
            $table->string('badge')->nullable();
            $table->string('image')->nullable();
            $table->text('summary')->nullable();
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('status')->default(true)->index();
            $table->timestamps();

            $table->index(['status', 'type', 'starts_at', 'ends_at']);
        });

        Schema::create('deal_campaign_product', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deal_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['deal_campaign_id', 'product_id']);
        });

        $permissions = collect(['view', 'create', 'edit', 'delete'])
            ->map(fn (string $action): array => [
                'name' => "{$action} deals",
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
        Schema::dropIfExists('deal_campaign_product');
        Schema::dropIfExists('deal_campaigns');

        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['view deals', 'create deals', 'edit deals', 'delete deals'])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
