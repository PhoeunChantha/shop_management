<?php

namespace App\Models;

use App\Observers\ColorObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[ObservedBy(ColorObserver::class)]
class Color extends Model
{
    public function attributeValues(): MorphMany
    {
        return $this->morphMany(AttributeValue::class, 'source');
    }

    /**
     * Whether this color is used by any product variant (blocks deletion).
     */
    public function isInUse(): bool
    {
        return $this->attributeValues()->whereHas('variants')->exists();
    }

    protected $fillable = [
        'name',
        'code',
        'hex_code',
        'image',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Filter colors by a search term against name/code. Skips filtering when blank.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(
            filled($term),
            fn (Builder $query) => $query->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            })
        );
    }
}
