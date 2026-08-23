<?php

declare(strict_types=1);

namespace App\Enums;

enum ConversationStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Closed => 'Closed',
        };
    }

    /**
     * Badge tone used by the admin inbox.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Open => 'success',
            self::Closed => 'muted',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
