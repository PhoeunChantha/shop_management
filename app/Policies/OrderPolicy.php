<?php

declare(strict_types=1);

namespace App\Policies;

class OrderPolicy extends AdminRolePolicy
{
    protected string $subject = 'orders';
}
