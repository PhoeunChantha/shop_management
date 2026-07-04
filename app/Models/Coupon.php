<?php

namespace App\Models;

use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_spend',
        'max_discount',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'type' => CouponType::class,
        'value' => 'decimal:2',
        'min_spend' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'status' => 'boolean',
    ];

    /* -------------------------------------------------------------------------
     | Scopes
     |-----------------------------------------------------------------------*/

    /** Coupons that are enabled, within their date window, and under their usage cap. */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->where(fn (Builder $q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'));
    }

    /** Match a coupon code (case-insensitive). */
    public function scopeCode(Builder $query, string $code): Builder
    {
        return $query->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))]);
    }

    /* -------------------------------------------------------------------------
     | Domain helpers
     |-----------------------------------------------------------------------*/

    public function hasStarted(): bool
    {
        return $this->starts_at === null || $this->starts_at->isPast();
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function reachedLimit(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    public function isValid(): bool
    {
        return $this->status && $this->hasStarted() && ! $this->hasExpired() && ! $this->reachedLimit();
    }

    /**
     * Discount amount this coupon yields for a given subtotal (respects min-spend and cap).
     */
    public function discountFor(float $subtotal): float
    {
        if (! $this->isValid() || ($this->min_spend !== null && $subtotal < (float) $this->min_spend)) {
            return 0.0;
        }

        $discount = $this->type === CouponType::Percentage
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        if ($this->max_discount !== null) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return round(min($discount, $subtotal), 2);
    }
}
