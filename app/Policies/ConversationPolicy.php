<?php

declare(strict_types=1);

namespace App\Policies;

class ConversationPolicy extends AdminRolePolicy
{
    protected string $subject = 'chats';
}
