<?php

declare(strict_types=1);

namespace App\Policies;

class PagePolicy extends AdminRolePolicy
{
    protected string $subject = 'pages';
}
