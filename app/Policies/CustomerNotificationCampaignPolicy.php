<?php

declare(strict_types=1);

namespace App\Policies;

class CustomerNotificationCampaignPolicy extends AdminRolePolicy
{
    protected string $subject = 'customer notifications';
}
