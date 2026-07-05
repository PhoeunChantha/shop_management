<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductType: string
{
    case Single = 'single';
    case Variable = 'variable';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single product',
            self::Variable => 'Variable (with variants)',
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
