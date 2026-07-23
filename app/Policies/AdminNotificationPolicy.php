<?php

declare(strict_types=1);

namespace App\Policies;

class AdminNotificationPolicy extends AdminRolePolicy
{
    protected string $subject = 'notifications';
}
