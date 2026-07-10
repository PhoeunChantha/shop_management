<?php

declare(strict_types=1);

namespace App\Policies;

class BannerPolicy extends AdminRolePolicy
{
    protected string $subject = 'banners';
}
