<?php

declare(strict_types=1);

namespace App\Enums;

enum ChatSenderRole: string
{
    case Customer = 'customer';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Staff => 'Support',
        };
    }
}
