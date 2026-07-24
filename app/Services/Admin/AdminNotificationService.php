<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Models\AbandonedCart;
use App\Models\AdminNotification;
use App\Models\DealCampaign;
use App\Models\MediaAsset;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

final class AdminNotificationService
{
    public function refreshGenerated(): void
    {
        if (! $this->ready()) {
            return;
        }

        $this->collectOrders();
        $this->collectReturns();
        $this->collectStock();
        $this->collectReviews();
        $this->collectMedia();
        $this->collectDeals();
        $this->collectAbandonedCarts();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return AdminNotification::query()
            ->search($filters['search'] ?? null)
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->when(($filters['state'] ?? null) === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when(($filters['state'] ?? null) === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        return [
            'total' => AdminNotification::count(),
            'unread' => AdminNotification::unread()->count(),
            'critical' => AdminNotification::unread()->where('priority', 'critical')->count(),
            'today' => AdminNotification::whereDate('created_at', today())->count(),
        ];
    }

    public function recentForHeader(int $limit = 6): Collection
    {
        if (! $this->ready()) {
            return new Collection();
        }

        return AdminNotification::query()
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function unreadCount(): int
    {
        return $this->ready() ? AdminNotification::unread()->count() : 0;
    }

    public function markRead(AdminNotification $notification): void
    {
        $notification->forceFill(['read_at' => now()])->save();
    }

    public function markUnread(AdminNotification $notification): void
    {
        $notification->forceFill(['read_at' => null])->save();
    }

    public function markAllRead(): int
    {
        return AdminNotification::unread()->update(['read_at' => now()]);
    }

    private function ready(): bool
    {
        return Schema::hasTable('admin_notifications');
    }

    private function collectOrders(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Order::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->where('status', OrderStatus::Pending->value)
            ->latest()
            ->limit(20)
            ->get(['id', 'order_number', 'customer_name', 'grand_total', 'created_at'])
            ->each(fn (Order $order) => $this->upsertGenerated(
                "new_order:{$order->id}",
                'new_order',
                'info',
                "New order {$order->order_number}",
                "{$order->customer_name} placed a $".number_format((float) $order->grand_total, 2).' order.',
                route('admin.orders.show', $order->id),
                'orders',
                $order->id,
                now()->addDays(10),
            ));

        Order::query()
            ->where('payment_status', PaymentStatus::Unpaid->value)
            ->latest()
            ->limit(20)
            ->get(['id', 'order_number', 'customer_name', 'grand_total', 'created_at'])
            ->each(fn (Order $order) => $this->upsertGenerated(
                "unpaid_order:{$order->id}",
                'unpaid_order',
                'warning',
                "Payment pending {$order->order_number}",
                "{$order->customer_name} still has an unpaid $".number_format((float) $order->grand_total, 2).' order.',
                route('admin.orders.show', $order->id),
                'orders',
                $order->id,
                null,
            ));
    }

    private function collectReturns(): void
    {
        if (! Schema::hasTable('return_requests')) {
            return;
        }

        ReturnRequest::query()
            ->with('order:id,order_number,customer_name')
            ->where('status', 'requested')
            ->latest()
            ->limit(20)
            ->get()
            ->each(fn (ReturnRequest $return) => $this->upsertGenerated(
                "return_request:{$return->id}",
                'return_request',
                'critical',
                "Return needs review {$return->return_number}",
                ($return->order?->customer_name ?? 'Customer').' requested a return for '.($return->order?->order_number ?? 'an order').'.',
                route('admin.returns.show', $return),
                'return_requests',
                $return->id,
                null,
            ));
    }

    private function collectStock(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Product::query()
            ->withSum('variants', 'stock')
            ->where('status', 'active')
            ->latest('updated_at')
            ->limit(150)
            ->get(['id', 'name', 'product_type', 'stock', 'low_stock_alert', 'status'])
            ->filter(fn (Product $product): bool => $product->total_stock <= 0 || ($product->low_stock_alert > 0 && $product->total_stock <= $product->low_stock_alert))
            ->take(25)
            ->each(function (Product $product): void {
                $out = $product->total_stock <= 0;

                $this->upsertGenerated(
                    ($out ? 'out_of_stock' : 'low_stock').":{$product->id}",
                    $out ? 'out_of_stock' : 'low_stock',
                    $out ? 'critical' : 'warning',
                    ($out ? 'Out of stock: ' : 'Low stock: ').$product->name,
                    'Current stock is '.$product->total_stock.'. Review inventory before this affects sales.',
                    route('admin.inventory.show', $product->id),
                    'products',
                    $product->id,
                    null,
                );
            });
    }

    private function collectReviews(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        Review::query()
            ->with('product:id,name')
            ->where('status', ReviewStatus::Pending->value)
            ->latest()
            ->limit(20)
            ->get()
            ->each(fn (Review $review) => $this->upsertGenerated(
                "pending_review:{$review->id}",
                'pending_review',
                'info',
                'Review waiting for moderation',
                ($review->author_name ?: 'A customer').' reviewed '.($review->product?->name ?? 'a product').'.',
                route('admin.reviews.index', ['search' => $review->author_name]),
                'reviews',
                $review->id,
                null,
            ));
    }

    private function collectMedia(): void
    {
        if (! Schema::hasTable('media_assets') || ! Schema::hasColumn('media_assets', 'optimization_status')) {
            return;
        }

        MediaAsset::query()
            ->whereIn('optimization_status', ['pending', 'failed'])
            ->latest()
            ->limit(20)
            ->get(['id', 'original_name', 'filename', 'optimization_status', 'created_at'])
            ->each(fn (MediaAsset $media) => $this->upsertGenerated(
                "media_optimization:{$media->id}",
                'media_optimization',
                $media->optimization_status === 'failed' ? 'warning' : 'info',
                $media->optimization_status === 'failed' ? 'Media optimization failed' : 'Media needs optimization',
                $media->original_name ?: $media->filename,
                route('admin.media.index', ['search' => $media->original_name ?: $media->filename]),
                'media_assets',
                $media->id,
                null,
            ));
    }

    private function collectDeals(): void
    {
        if (! Schema::hasTable('deal_campaigns')) {
            return;
        }

        DealCampaign::query()
            ->where('status', true)
            ->whereBetween('ends_at', [now(), now()->addHours(48)])
            ->orderBy('ends_at')
            ->limit(20)
            ->get(['id', 'title', 'ends_at'])
            ->each(fn (DealCampaign $deal) => $this->upsertGenerated(
                "deal_expiring:{$deal->id}",
                'deal_expiring',
                'warning',
                'Deal ending soon',
                $deal->title.' ends '.$deal->ends_at?->diffForHumans().'.',
                route('admin.deals.show', $deal),
                'deal_campaigns',
                $deal->id,
                $deal->ends_at?->copy()->addDay(),
            ));
    }

    private function collectAbandonedCarts(): void
    {
        if (! Schema::hasTable('abandoned_carts')) {
            return;
        }

        AbandonedCart::query()
            ->whereIn('status', ['new', 'contacted'])
            ->where('subtotal', '>=', 100)
            ->where('last_activity_at', '<=', now()->subHour())
            ->latest('last_activity_at')
            ->limit(20)
            ->get(['id', 'customer_name', 'customer_email', 'subtotal', 'last_activity_at'])
            ->each(fn (AbandonedCart $cart) => $this->upsertGenerated(
                "abandoned_cart:{$cart->id}",
                'abandoned_cart',
                'warning',
                'High-value cart abandoned',
                ($cart->customer_name ?: $cart->customer_email ?: 'Guest customer').' left $'.number_format((float) $cart->subtotal, 2).' in cart.',
                route('admin.abandoned-carts.show', $cart),
                'abandoned_carts',
                $cart->id,
                null,
            ));
    }

    private function upsertGenerated(
        string $fingerprint,
        string $type,
        string $priority,
        string $title,
        ?string $body,
        ?string $url,
        string $sourceType,
        int $sourceId,
        mixed $expiresAt,
    ): void {
        AdminNotification::updateOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'type' => $type,
                'priority' => $priority,
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'expires_at' => $expiresAt,
            ],
        );
    }
}
