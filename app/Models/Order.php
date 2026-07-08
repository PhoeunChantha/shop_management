<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Services\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Order extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Auto-assign an order number (using the store prefix) when one isn't set.
        static::creating(function (Order $order): void {
            if (blank($order->order_number)) {
                $order->order_number = self::generateNumber();
            }
        });
    }

    /**
     * Build a unique order number: "{prefix}{YEAR}-{6-digit}", e.g. UT-2026-000123.
     * Prefix comes from Settings → Orders.
     */
    public static function generateNumber(): string
    {
        $prefix = app(SettingService::class)->orderPrefix();

        do {
            $number = $prefix.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('order_number', $number)->exists());

        return $number;
    }

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'shipping_city',
        'shipping_zip',
        'shipping_country',
        'subtotal',
        'discount_total',
        'shipping_total',
        'tax_total',
        'grand_total',
        'coupon_id',
        'coupon_code',
        'shipping_method',
        'tracking_number',
        'payment_method',
        'payment_status',
        'paid_at',
        'customer_note',
        'admin_note',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_at' => 'immutable_datetime',
            'placed_at' => 'immutable_datetime',
        ];
    }

    /* ---------------- Relationships ---------------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest();
    }

    /**
     * Append an entry to the order's activity log.
     */
    public function logEvent(string $type, string $title, ?string $body = null, ?int $userId = null): OrderEvent
    {
        return $this->events()->create([
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'title' => $title,
            'body' => $body,
        ]);
    }

    /* ---------------- Scopes ---------------- */

    /** Filter by order number / customer name / email. Skips filtering when blank. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(
            filled($term),
            fn (Builder $query) => $query->where(function (Builder $query) use ($term) {
                $query->where('order_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%");
            })
        );
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, fn (Builder $query) => $query->where('status', $status));
    }

    /* ---------------- Accessors & helpers ---------------- */

    /** Total quantity across the order's lines (uses the eager-loaded sum when present). */
    protected function totalQuantity(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) ($this->details_sum_quantity ?? $this->details()->sum('quantity')),
        );
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }
}
