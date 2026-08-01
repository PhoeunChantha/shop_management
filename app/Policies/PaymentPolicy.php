<?php

declare(strict_types=1);

namespace App\Policies;

class PaymentPolicy extends AdminRolePolicy
{
    protected string $subject = 'payments';
}
