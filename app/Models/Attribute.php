<?php

namespace App\Models;

use App\Enums\AttributeType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'type' => AttributeType::class,
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }

    public function activeValues(): HasMany
    {
        return $this->values()->where('status', true);
    }

    /**
     * Filter attributes by a search term against the name. Skips filtering when blank.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(
            filled($term),
            fn (Builder $query) => $query->where('name', 'like', "%{$term}%")
        );
    }
}
