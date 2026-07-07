<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    /** CSS status-chip modifier used in the admin tables. */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'st-draft',
            self::Paid, self::Processing => 'st-active',
            self::Shipped => 'st-new',
            self::Delivered => 'st-active',
            self::Cancelled, self::Refunded => 'st-inactive',
        };
    }

    /** Statuses this one may transition to (admin workflow). */
    public function transitionsTo(): array
    {
        return match ($this) {
            self::Pending => [self::Paid, self::Cancelled],
            self::Paid => [self::Processing, self::Refunded, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered, self::Refunded],
            self::Delivered => [self::Refunded],
            self::Cancelled, self::Refunded => [],
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Delivered, self::Cancelled, self::Refunded], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Cancelled, self::Refunded], true);
    }

    /**
     * Ordered happy-path stages rendered by the fulfilment stepper.
     *
     * @return array<int, self>
     */
    public static function flow(): array
    {
        return [self::Pending, self::Paid, self::Processing, self::Shipped, self::Delivered];
    }

    /** Zero-based position of this status in the happy-path flow (‑1 if off-path). */
    public function flowIndex(): int
    {
        $pos = array_search($this, self::flow(), true);

        return $pos === false ? -1 : $pos;
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
