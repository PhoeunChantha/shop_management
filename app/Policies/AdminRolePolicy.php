<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Base authorization for admin catalog resources. Each ability maps to a spatie
 * permission built from the concrete policy's $subject, e.g. "edit products".
 * Concrete policies set $subject; override a method for special cases.
 */
abstract class AdminRolePolicy
{
    /**
     * Permission subject for this resource, e.g. 'products'. Set by each concrete policy.
     */
    protected string $subject = '';

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo("view {$this->subject}");
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo("view {$this->subject}");
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo("create {$this->subject}");
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo("edit {$this->subject}");
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo("delete {$this->subject}");
    }
}
