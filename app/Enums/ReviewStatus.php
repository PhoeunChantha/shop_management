<?php

declare(strict_types=1);

namespace App\Enums;

enum ReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    /** CSS status-chip modifier. */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'st-draft',
            self::Approved => 'st-active',
            self::Rejected => 'st-inactive',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'fa-clock',
            self::Approved => 'fa-circle-check',
            self::Rejected => 'fa-circle-xmark',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
