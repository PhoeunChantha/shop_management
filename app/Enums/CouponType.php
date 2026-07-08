<?php

declare(strict_types=1);

namespace App\Enums;

enum CouponType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage (%)',
            self::Fixed => 'Fixed amount',
        };
    }

    /**
     * Symbol shown next to the value.
     */
    public function symbol(): string
    {
        return match ($this) {
            self::Percentage => '%',
            self::Fixed => '$',
        };
    }

    /**
     * All cases as [value => label] for select inputs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
