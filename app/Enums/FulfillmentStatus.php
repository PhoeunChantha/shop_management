<?php

declare(strict_types=1);

namespace App\Enums;

enum FulfillmentStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case Partial = 'partial';
    case Fulfilled = 'fulfilled';

    public function label(): string
    {
        return match ($this) {
            self::Unfulfilled => 'Unfulfilled',
            self::Partial => 'Partial',
            self::Fulfilled => 'Fulfilled',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Unfulfilled => 'st-draft',
            self::Partial => 'st-new',
            self::Fulfilled => 'st-active',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
