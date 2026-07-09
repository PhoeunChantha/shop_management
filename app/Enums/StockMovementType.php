<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case Restock = 'restock';
    case Correction = 'correction';
    case Damage = 'damage';
    case Return = 'return';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Initial = 'initial';

    public function label(): string
    {
        return match ($this) {
            self::Restock => 'Restock',
            self::Correction => 'Correction',
            self::Damage => 'Damage / Loss',
            self::Return => 'Customer return',
            self::Adjustment => 'Manual adjustment',
            self::Sale => 'Sale',
            self::Initial => 'Initial stock',
        };
    }

    /** CSS status-chip modifier. */
    public function badge(): string
    {
        return match ($this) {
            self::Restock, self::Return, self::Initial => 'st-active',
            self::Damage, self::Sale => 'st-inactive',
            self::Correction, self::Adjustment => 'st-new',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Restock => 'fa-truck-ramp-box',
            self::Correction => 'fa-pen-ruler',
            self::Damage => 'fa-triangle-exclamation',
            self::Return => 'fa-rotate-left',
            self::Adjustment => 'fa-sliders',
            self::Sale => 'fa-cart-shopping',
            self::Initial => 'fa-flag-checkered',
        };
    }

    /**
     * Reasons an admin can pick when manually adjusting stock.
     *
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        return collect([self::Restock, self::Correction, self::Damage, self::Return, self::Adjustment])
            ->mapWithKeys(fn (self $t) => [$t->value => $t->label()])
            ->all();
    }
}
