<?php

declare(strict_types=1);

namespace App\Policies;

class CouponPolicy extends AdminRolePolicy
{
    protected string $subject = 'coupons';
}
