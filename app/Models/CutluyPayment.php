<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CutluyPayment extends Model
{
    /** Statuses in which the customer can still pay this QR. */
    public const OPEN = ['pending', 'scanned'];

    protected $fillable = [
        'payment_id',
        'payable_type',
        'payable_id',
        'reference_id',
        'idempotency_key',
        'amount',
        'currency',
        'status',
        'checkout_url',
        'qr_string',
        'paid_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'immutable_datetime',
            'payload' => 'array',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
