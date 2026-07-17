<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    public const TYPES = [
        'new_order' => 'New orders',
        'unpaid_order' => 'Unpaid orders',
        'return_request' => 'Returns',
        'low_stock' => 'Low stock',
        'out_of_stock' => 'Out of stock',
        'pending_review' => 'Reviews',
        'media_optimization' => 'Media',
        'deal_expiring' => 'Deals',
        'abandoned_cart' => 'Abandoned carts',
    ];

    public const PRIORITIES = [
        'info' => 'Info',
        'warning' => 'Warning',
        'critical' => 'Critical',
    ];

    protected $fillable = [
        'fingerprint',
        'type',
        'priority',
        'title',
        'body',
        'url',
        'source_type',
        'source_id',
        'read_at',
        'expires_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(filled($term), function (Builder $query) use ($term): void {
            $query->where(function (Builder $query) use ($term): void {
                $query->where('title', 'like', "%{$term}%")
                    ->orWhere('body', 'like', "%{$term}%");
            });
        });
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? str($this->type)->headline()->value();
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst($this->priority);
    }

    public function priorityBadge(): string
    {
        return match ($this->priority) {
            'critical' => 'st-inactive',
            'warning' => 'st-new',
            default => 'st-active',
        };
    }

    public function icon(): string
    {
        return match ($this->type) {
            'new_order', 'unpaid_order' => 'fa-receipt',
            'return_request' => 'fa-rotate-left',
            'low_stock', 'out_of_stock' => 'fa-box-open',
            'pending_review' => 'fa-star-half-stroke',
            'media_optimization' => 'fa-photo-film',
            'deal_expiring' => 'fa-tags',
            'abandoned_cart' => 'fa-cart-arrow-down',
            default => 'fa-bell',
        };
    }

    public function tone(): string
    {
        return match ($this->priority) {
            'critical' => 'notification-tone--critical',
            'warning' => 'notification-tone--warning',
            default => 'notification-tone--info',
        };
    }
}
