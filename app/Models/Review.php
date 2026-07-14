<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Observers\ReviewObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(ReviewObserver::class)]
class Review extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'author_name',
        'rating',
        'title',
        'body',
        'status',
        'is_verified',
    ];

    protected $casts = [
        'rating' => 'integer',
        'status' => ReviewStatus::class,
        'is_verified' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(
            filled($term),
            fn (Builder $query) => $query->where(function (Builder $query) use ($term) {
                $query->where('author_name', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('body', 'like', "%{$term}%");
            })
        );
    }
}
