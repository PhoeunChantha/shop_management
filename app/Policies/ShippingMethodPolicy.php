<?php

declare(strict_types=1);

namespace App\Policies;

class ShippingMethodPolicy extends AdminRolePolicy
{
    protected string $subject = 'shipping';
}
