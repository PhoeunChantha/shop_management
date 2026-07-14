<?php

declare(strict_types=1);

namespace App\Policies;

class FaqPolicy extends AdminRolePolicy
{
    protected string $subject = 'faqs';
}
