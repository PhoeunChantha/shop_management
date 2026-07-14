<?php

declare(strict_types=1);

namespace App\Policies;

class TaxRulePolicy extends AdminRolePolicy
{
    protected string $subject = 'taxes';
}
