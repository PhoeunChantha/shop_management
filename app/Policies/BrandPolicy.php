<?php

declare(strict_types=1);

namespace App\Policies;

class BrandPolicy extends AdminRolePolicy
{
    protected string $subject = 'brands';
}
