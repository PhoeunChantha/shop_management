<?php

declare(strict_types=1);

namespace App\Policies;

class AnnouncementPolicy extends AdminRolePolicy
{
    protected string $subject = 'announcements';
}
