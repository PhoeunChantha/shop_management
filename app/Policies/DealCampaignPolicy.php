<?php

declare(strict_types=1);

namespace App\Policies;

class DealCampaignPolicy extends AdminRolePolicy
{
    protected string $subject = 'deals';
}
