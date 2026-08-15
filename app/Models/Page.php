<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    /** Built-in pages the storefront resolves by key (not deletable in admin). */
    public const SYSTEM_KEYS = ['about', 'privacy', 'terms'];

    protected $fillable = [
        'title',
        'slug',
        'page_key',
        'content',
        'seo_title',
        'seo_description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /** A built-in page (about/privacy/terms) with a stable key. */
    public function isSystem(): bool
    {
        return filled($this->page_key);
    }

    public function scopeKey(Builder $query, string $key): Builder
    {
        return $query->where('page_key', $key);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(
            filled($term),
            fn (Builder $query) => $query->where(function (Builder $query) use ($term) {
                $query->where('title', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            })
        );
    }
}
