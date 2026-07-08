<?php

declare(strict_types=1);

namespace App\Enums;

enum AttributeType: string
{
    case Size = 'size';
    case Color = 'color';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Size => 'Size (from Sizes)',
            self::Color => 'Color (from Colors)',
            self::Custom => 'Custom values',
        };
    }

    /** Does this type draw its values from a managed master table? */
    public function isLinked(): bool
    {
        return $this !== self::Custom;
    }

    /** The source master table for a linked type. */
    public function sourceTable(): ?string
    {
        return match ($this) {
            self::Size => 'sizes',
            self::Color => 'colors',
            self::Custom => null,
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
