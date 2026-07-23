<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RoleService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Role::query()
            ->with('permissions')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('permissions', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function permissions(): \Illuminate\Database\Eloquent\Collection
    {
        return Permission::orderBy('id')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        $role = Role::create(['name' => $data['name']]);
        $this->syncPermissions($role, $data['permissions'] ?? []);

        return $role;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        $role->update(['name' => $data['name']]);
        $this->syncPermissions($role, $data['permissions'] ?? []);

        return $role;
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    private function syncPermissions(Role $role, array $ids): void
    {
        $permissions = $ids === []
            ? collect()
            : Permission::whereIn('id', $ids)->get();

        $role->syncPermissions($permissions);
    }
}
