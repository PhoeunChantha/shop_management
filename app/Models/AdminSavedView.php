<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSavedView extends Model
{
    protected $fillable = [
        'user_id',
        'scope',
        'name',
        'route_name',
        'query',
        'icon',
        'color',
        'is_global',
        'sort_order',
    ];

    protected $casts = [
        'query' => 'array',
        'is_global' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleTo(Builder $query, ?int $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId): void {
            $query->where('is_global', true)
                ->when($userId, fn (Builder $query) => $query->orWhere('user_id', $userId));
        });
    }
}
