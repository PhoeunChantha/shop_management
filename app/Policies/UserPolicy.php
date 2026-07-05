<?php

declare(strict_types=1);

namespace App\Policies;

class UserPolicy extends AdminRolePolicy
{
    protected string $subject = 'users';
}
