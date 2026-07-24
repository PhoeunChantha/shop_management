<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class PermissionAuditService
{
    private const RISKY_TERMS = [
        'delete',
        'setting',
        'permission',
        'role',
        'user',
        'supplier',
        'purchase order',
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(array $filters = []): array
    {
        $roles = Role::query()
            ->with(['permissions' => fn ($query) => $query->orderBy('name')])
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $permissions = Permission::query()
            ->orderBy('name')
            ->get();

        $rolePermissionSets = $roles->mapWithKeys(fn (Role $role) => [
            $role->id => $role->permissions->pluck('name')->flip(),
        ]);

        $matrix = $permissions->map(fn (Permission $permission) => $this->matrixRow($permission, $roles, $rolePermissionSets));
        $roleSummaries = $this->roleSummaries($roles, $rolePermissionSets);
        $riskyPermissions = $this->riskyPermissions($permissions, $roles, $rolePermissionSets);
        $comparison = $this->comparison($filters, $roles, $matrix);

        return [
            'roles' => $roles,
            'permissions' => $permissions,
            'matrix' => $matrix,
            'roleSummaries' => $roleSummaries,
            'riskyPermissions' => $riskyPermissions,
            'comparison' => $comparison,
            'directPermissions' => $this->directPermissions(),
            'staleAdmins' => $this->staleAdmins(),
            'stats' => [
                'roles' => $roles->count(),
                'permissions' => $permissions->count(),
                'risky_permissions' => $riskyPermissions->count(),
                'direct_permissions' => $this->directPermissions()->count(),
            ],
            'filters' => $filters,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string>>
     */
    public function exportRows(array $filters = []): array
    {
        $data = $this->overview($filters);
        $roles = $data['roles'];

        $rows = [];
        $rows[] = array_merge(['Permission', 'Subject', 'Action'], $roles->pluck('name')->all());

        foreach ($data['matrix'] as $row) {
            $rows[] = array_merge([
                $row['name'],
                $row['subject'],
                $row['action'],
            ], $roles->map(fn (Role $role) => $row['roles'][$role->id] ? 'Yes' : 'No')->all());
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Role>  $roles
     * @param  Collection<int, Collection<string, int>>  $rolePermissionSets
     * @return array<string, mixed>
     */
    private function matrixRow(Permission $permission, Collection $roles, Collection $rolePermissionSets): array
    {
        [$action, $subject] = $this->splitPermissionName($permission->name);

        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'action' => $action,
            'subject' => $subject,
            'is_risky' => $this->isRisky($permission->name),
            'roles' => $roles->mapWithKeys(fn (Role $role) => [
                $role->id => $rolePermissionSets->get($role->id, collect())->has($permission->name),
            ]),
        ];
    }

    /**
     * @param  Collection<int, Role>  $roles
     * @param  Collection<int, Collection<string, int>>  $rolePermissionSets
     * @return Collection<int, array<string, mixed>>
     */
    private function roleSummaries(Collection $roles, Collection $rolePermissionSets): Collection
    {
        return $roles->map(function (Role $role) use ($rolePermissionSets): array {
            $permissionNames = $rolePermissionSets->get($role->id, collect())->keys();

            return [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users_count,
                'permissions_count' => $permissionNames->count(),
                'risky_count' => $permissionNames->filter(fn (string $name) => $this->isRisky($name))->count(),
            ];
        });
    }

    /**
     * @param  Collection<int, Permission>  $permissions
     * @param  Collection<int, Role>  $roles
     * @param  Collection<int, Collection<string, int>>  $rolePermissionSets
     * @return Collection<int, array<string, mixed>>
     */
    private function riskyPermissions(Collection $permissions, Collection $roles, Collection $rolePermissionSets): Collection
    {
        return $permissions
            ->filter(fn (Permission $permission) => $this->isRisky($permission->name))
            ->map(function (Permission $permission) use ($roles, $rolePermissionSets): array {
                [$action, $subject] = $this->splitPermissionName($permission->name);

                return [
                    'name' => $permission->name,
                    'action' => $action,
                    'subject' => $subject,
                    'roles' => $roles
                        ->filter(fn (Role $role) => $rolePermissionSets->get($role->id, collect())->has($permission->name))
                        ->pluck('name')
                        ->values(),
                ];
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  Collection<int, Role>  $roles
     * @param  Collection<int, array<string, mixed>>  $matrix
     * @return array<string, mixed>|null
     */
    private function comparison(array $filters, Collection $roles, Collection $matrix): ?array
    {
        $roleA = $roles->firstWhere('id', (int) ($filters['role_a'] ?? 0));
        $roleB = $roles->firstWhere('id', (int) ($filters['role_b'] ?? 0));

        if (! $roleA || ! $roleB || $roleA->is($roleB)) {
            return null;
        }

        return [
            'left' => $roleA,
            'right' => $roleB,
            'differences' => $matrix
                ->filter(fn (array $row) => $row['roles'][$roleA->id] !== $row['roles'][$roleB->id])
                ->values(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function directPermissions(): Collection
    {
        return DB::table('model_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
            ->join('users', 'users.id', '=', 'model_has_permissions.model_id')
            ->where('model_has_permissions.model_type', User::class)
            ->orderBy('users.name')
            ->orderBy('permissions.name')
            ->get(['users.name as user_name', 'users.email', 'permissions.name as permission_name'])
            ->map(fn (object $row) => [
                'user_name' => $row->user_name,
                'email' => $row->email,
                'permission_name' => $row->permission_name,
            ]);
    }

    /**
     * @return Collection<int, User>
     */
    private function staleAdmins(): Collection
    {
        return User::query()
            ->with('roles')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'manager', 'staff']))
            ->where('updated_at', '<=', now()->subDays(90))
            ->orderBy('updated_at')
            ->limit(8)
            ->get();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitPermissionName(string $name): array
    {
        if (! str_contains($name, ' ')) {
            return ['custom', Str::headline($name)];
        }

        [$action, $subject] = explode(' ', $name, 2);

        return [Str::headline($action), Str::headline($subject)];
    }

    private function isRisky(string $permissionName): bool
    {
        $name = Str::lower($permissionName);

        foreach (self::RISKY_TERMS as $term) {
            if (str_contains($name, $term)) {
                return true;
            }
        }

        return false;
    }
}
