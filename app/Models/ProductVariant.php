<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'size_id',
        'color_id',
        'sku',
        'barcode',
        'image',
        'stock',
        'low_stock_alert',
        'price',
        'cost_price',
        'weight',
        'status',
    ];

    protected $casts = [
        'stock' => 'integer',
        'low_stock_alert' => 'integer',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockMovement::class, 'variant_id')->latest();
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    /**
     * The generic attribute values that define this variant (Color=Black, Size=M …).
     * Supersedes the legacy size()/color() relations.
     */
    public function values(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_value');
    }

    /**
     * Human label built from the variant's attribute values, e.g. "Black / Medium".
     */
    public function getVariantLabelAttribute(): string
    {
        return $this->relationLoaded('values')
            ? $this->values->pluck('value')->join(' / ')
            : $this->values()->pluck('value')->join(' / ');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->low_stock_alert > 0 && $this->stock <= $this->low_stock_alert;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Imageurl($this->image, 'variants') : null;
    }
}
