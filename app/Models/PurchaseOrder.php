<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    public const STATUSES = [
        'draft' => 'Draft',
        'ordered' => 'Ordered',
        'partial' => 'Partially received',
        'received' => 'Received',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'po_number',
        'supplier_id',
        'user_id',
        'status',
        'ordered_at',
        'expected_at',
        'received_at',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'ordered_at' => 'date',
        'expected_at' => 'date',
        'received_at' => 'date',
        'subtotal' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(filled($term), fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
            $query->where('po_number', 'like', "%{$term}%")
                ->orWhereHas('supplier', fn (Builder $query) => $query->where('name', 'like', "%{$term}%"));
        }));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'received' => 'st-active',
            'ordered', 'partial' => 'st-new',
            'cancelled' => 'st-inactive',
            default => 'st-draft',
        };
    }
}
