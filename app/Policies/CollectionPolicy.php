<?php

declare(strict_types=1);

namespace App\Policies;

class CollectionPolicy extends AdminRolePolicy
{
    protected string $subject = 'collections';
}
