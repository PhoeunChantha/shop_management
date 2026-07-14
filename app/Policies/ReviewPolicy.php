<?php

declare(strict_types=1);

namespace App\Policies;

class ReviewPolicy extends AdminRolePolicy
{
    protected string $subject = 'reviews';
}
