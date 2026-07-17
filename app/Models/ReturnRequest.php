<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ReturnRequest extends Model
{
    use HasFactory;

    public const STATUSES = [
        'requested' => 'Requested',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'received' => 'Received',
        'refunded' => 'Refunded',
    ];

    public const REFUND_STATUSES = [
        'not_refunded' => 'Not refunded',
        'pending' => 'Pending',
        'partial' => 'Partial',
        'refunded' => 'Refunded',
    ];

    public const REASONS = [
        'wrong_size' => 'Wrong size',
        'damaged' => 'Damaged item',
        'not_as_described' => 'Not as described',
        'changed_mind' => 'Changed mind',
        'late_delivery' => 'Late delivery',
        'other' => 'Other',
    ];

    protected $fillable = [
        'return_number',
        'order_id',
        'user_id',
        'status',
        'refund_status',
        'reason',
        'customer_note',
        'admin_note',
        'requested_amount',
        'refund_amount',
        'requested_at',
        'approved_at',
        'received_at',
        'refunded_at',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReturnRequest $return): void {
            if (blank($return->return_number)) {
                $return->return_number = self::generateNumber();
            }

            $return->requested_at ??= now();
        });
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'RET-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('return_number', $number)->exists());

        return $number;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnRequestItem::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', function (Builder $query) use ($term): void {
            $query->where('return_number', 'like', "%{$term}%")
                ->orWhereHas('order', fn (Builder $query) => $query
                    ->where('order_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%"));
        });
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function refundStatusLabel(): string
    {
        return self::REFUND_STATUSES[$this->refund_status] ?? ucfirst($this->refund_status);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? ucfirst(str_replace('_', ' ', $this->reason));
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'requested' => 'st-draft',
            'approved', 'received' => 'st-new',
            'refunded' => 'st-active',
            'rejected' => 'st-inactive',
            default => 'st-draft',
        };
    }

    public function refundBadge(): string
    {
        return match ($this->refund_status) {
            'refunded' => PaymentStatus::Refunded->badge(),
            'partial' => PaymentStatus::PartiallyRefunded->badge(),
            'pending' => 'st-draft',
            default => 'st-archived',
        };
    }
}
