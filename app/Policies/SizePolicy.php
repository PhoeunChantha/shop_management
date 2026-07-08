<?php

declare(strict_types=1);

namespace App\Policies;

class SizePolicy extends AdminRolePolicy
{
    protected string $subject = 'sizes';
}
