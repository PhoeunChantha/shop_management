<?php

declare(strict_types=1);

namespace App\Policies;

class CurrencyPolicy extends AdminRolePolicy
{
    protected string $subject = 'currencies';
}
