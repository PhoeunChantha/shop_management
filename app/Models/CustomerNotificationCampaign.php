<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerNotificationCampaign extends Model
{
    protected $fillable = [
        'title',
        'message',
        'url',
        'audience_type',
        'audience_summary',
        'recipient_count',
        'registered_recipient_count',
        'sent_by',
    ];

    protected $casts = [
        'recipient_count' => 'integer',
        'registered_recipient_count' => 'integer',
    ];

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(
            filled($term),
            fn (Builder $query) => $query->where('title', 'like', "%{$term}%")
        );
    }
}
