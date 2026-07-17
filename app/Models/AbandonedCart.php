<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbandonedCart extends Model
{
    public const STATUSES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'recovered' => 'Recovered',
        'ignored' => 'Ignored',
    ];

    protected $fillable = [
        'cart_token',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'item_count',
        'subtotal',
        'last_activity_at',
        'contacted_at',
        'recovered_at',
        'ignored_at',
        'admin_note',
        'metadata',
    ];

    protected $casts = [
        'item_count' => 'integer',
        'subtotal' => 'decimal:2',
        'last_activity_at' => 'datetime',
        'contacted_at' => 'datetime',
        'recovered_at' => 'datetime',
        'ignored_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AbandonedCartItem::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(filled($term), fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
            $query->where('customer_name', 'like', "%{$term}%")
                ->orWhere('customer_email', 'like', "%{$term}%")
                ->orWhere('customer_phone', 'like', "%{$term}%")
                ->orWhere('cart_token', 'like', "%{$term}%");
        }));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'recovered' => 'st-active',
            'contacted' => 'st-new',
            'ignored' => 'st-inactive',
            default => 'st-draft',
        };
    }
}
