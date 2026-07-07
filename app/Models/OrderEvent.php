<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderEvent extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'type',
        'title',
        'body',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** FontAwesome icon for the timeline, by event type. */
    public function icon(): string
    {
        return match ($this->type) {
            'created' => 'fa-cart-plus',
            'status' => 'fa-arrows-rotate',
            'payment' => 'fa-credit-card',
            'fulfilment' => 'fa-truck',
            'note' => 'fa-note-sticky',
            default => 'fa-circle-dot',
        };
    }
}
