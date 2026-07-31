<?php

declare(strict_types=1);

namespace App\Policies;

class NewsletterSubscriberPolicy extends AdminRolePolicy
{
    protected string $subject = 'subscribers';
}
