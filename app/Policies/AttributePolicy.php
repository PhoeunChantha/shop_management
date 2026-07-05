<?php

declare(strict_types=1);

namespace App\Policies;

class AttributePolicy extends AdminRolePolicy
{
    protected string $subject = 'attributes';
}
