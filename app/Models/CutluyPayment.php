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
        'expires_at',
        'paid_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expires_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'payload' => 'array',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Still payable: an open status whose QR has not run out. */
    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true) && ! $this->hasLapsed();
    }

    /** Past CutLuy's expiry time, whether or not the expired event has arrived. */
    public function hasLapsed(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Merchant name embedded in the KHQR string (EMV tag 59) — the name the
     * customer's banking app will show — or null when it cannot be read.
     */
    public function merchantName(): ?string
    {
        $qr = (string) $this->qr_string;

        for ($i = 0; $i + 4 <= strlen($qr);) {
            $tag = substr($qr, $i, 2);
            $length = substr($qr, $i + 2, 2);

            if (! ctype_digit($tag) || ! ctype_digit($length)) {
                return null;
            }

            if ($tag === '59') {
                return trim(substr($qr, $i + 4, (int) $length)) ?: null;
            }

            $i += 4 + (int) $length;
        }

        return null;
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
