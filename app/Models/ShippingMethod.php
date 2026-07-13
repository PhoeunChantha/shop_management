<?php

namespace App\Models;

use App\Enums\ShippingRateType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'rate',
        'free_over_amount',
        'delivery_time',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'type' => ShippingRateType::class,
        'rate' => 'decimal:2',
        'free_over_amount' => 'decimal:2',
        'sort_order' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Shipping cost for a given order subtotal.
     */
    public function costFor(float $subtotal): float
    {
        return match ($this->type) {
            ShippingRateType::Free => 0.0,
            ShippingRateType::FreeOver => $subtotal >= (float) $this->free_over_amount ? 0.0 : (float) $this->rate,
            ShippingRateType::Flat => (float) $this->rate,
        };
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(
            filled($term),
            fn (Builder $query) => $query->where('name', 'like', "%{$term}%")
        );
    }
}
