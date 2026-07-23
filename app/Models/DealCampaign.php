<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DealCampaign extends Model
{
    use HasFactory;

    public const TYPES = [
        'flash' => 'Flash Deal',
        'daily' => 'Deal Of The Day',
        'featured' => 'Featured Deal',
        'clearance' => 'Clearance Sale',
    ];

    protected $fillable = [
        'title',
        'slug',
        'type',
        'badge',
        'image',
        'summary',
        'discount_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'cta_text',
        'cta_url',
        'meta_title',
        'meta_description',
        'priority',
        'status',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'priority' => 'integer',
        'status' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'deal_campaign_product')->withTimestamps();
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        return $query->when($search !== '', function (Builder $query) use ($search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('badge', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        });
    }

    public function scopeType(Builder $query, ?string $type): Builder
    {
        return $query->when($type, fn (Builder $query) => $query->where('type', $type));
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Imageurl($this->image, 'deals') : null;
    }

    public function getLifecycleAttribute(): string
    {
        if (! $this->status) {
            return 'disabled';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'expired';
        }

        return 'active';
    }
}
