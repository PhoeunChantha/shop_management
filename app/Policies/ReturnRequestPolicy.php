<?php

declare(strict_types=1);

namespace App\Policies;

class ReturnRequestPolicy extends AdminRolePolicy
{
    protected string $subject = 'returns';
}
