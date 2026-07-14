<?php

declare(strict_types=1);

namespace App\Enums;

enum ShippingRateType: string
{
    case Flat = 'flat';
    case Free = 'free';
    case FreeOver = 'free_over';

    public function label(): string
    {
        return match ($this) {
            self::Flat => 'Flat rate',
            self::Free => 'Always free',
            self::FreeOver => 'Free over threshold',
        };
    }

    /** CSS status-chip modifier. */
    public function badge(): string
    {
        return match ($this) {
            self::Flat => 'st-draft',
            self::Free => 'st-active',
            self::FreeOver => 'st-new',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $t) => [$t->value => $t->label()])
            ->all();
    }
}
