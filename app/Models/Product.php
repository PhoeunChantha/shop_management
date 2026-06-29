<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sub_category_id',
        'name',
        'slug',
        'description',
        'price',
        'discount_type',
        'discount_amount',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Final price after applying the discount (never below zero).
     */
    public function getFinalPriceAttribute(): float
    {
        $price = (float) $this->price;
        $amount = (float) $this->discount_amount;

        $final = match ($this->discount_type) {
            'fixed' => $price - $amount,
            'percentage' => $price - ($price * $amount / 100),
            default => $price,
        };

        return max(0, round($final, 2));
    }

    /**
     * Whether the product actually carries a discount.
     */
    public function getHasDiscountAttribute(): bool
    {
        return in_array($this->discount_type, ['fixed', 'percentage'], true)
            && (float) $this->discount_amount > 0;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}
