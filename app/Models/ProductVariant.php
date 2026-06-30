<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'size_id',
        'color_id',
        'sku',
        'barcode',
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

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->low_stock_alert > 0 && $this->stock <= $this->low_stock_alert;
    }
}
