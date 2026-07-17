<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Models\AbandonedCart;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregates the admin dashboard for a selectable period (7d / 30d / 12m):
 * windowed KPI cards with trend + sparkline, a smooth SVG revenue area chart,
 * an orders-by-status breakdown, recent orders and low-stock items.
 *
 * Series are bucketed in PHP (daily or monthly) so queries stay portable.
 */
final class DashboardService
{
    private const PAID = ['paid', 'partially_refunded'];

    /** Supported ranges → [bucket unit, bucket count, label]. */
    private const RANGES = [
        '7d' => ['day', 7, '7 days'],
        '30d' => ['day', 30, '30 days'],
        '12m' => ['month', 12, '12 months'],
    ];

    /** KPI accent colour per tone. */
    private const TONE_COLOR = [
        'blue' => '#2563eb',
        'orange' => '#ea580c',
        'green' => '#059669',
        'violet' => '#7c3aed',
    ];

    /**
     * @return array<string, mixed>
     */
    public function overview(string $range = '30d'): array
    {
        $range = isset(self::RANGES[$range]) ? $range : '30d';
        [$unit, $count, $rangeLabel] = self::RANGES[$range];

        $buckets = $this->buckets($unit, $count);
        $keys = $buckets->pluck('key')->all();
        $curStart = $buckets->first()['date'];

        // Fetch once from the start of the *previous* window so trends are cheap.
        $windowStart = $unit === 'day'
            ? $curStart->copy()->subDays($count)
            : $curStart->copy()->subMonths($count);

        [$revSeries, $revPrev] = $this->split(
            Order::whereIn('payment_status', self::PAID)->where('created_at', '>=', $windowStart)
                ->get(['grand_total', 'placed_at', 'created_at']),
            fn (Order $o) => $o->placed_at ?? $o->created_at,
            fn (Order $o) => (float) $o->grand_total,
            $keys, $unit, $curStart,
        );

        [$ordSeries, $ordPrev] = $this->split(
            Order::where('created_at', '>=', $windowStart)->get(['placed_at', 'created_at']),
            fn (Order $o) => $o->placed_at ?? $o->created_at,
            fn () => 1,
            $keys, $unit, $curStart,
        );

        [$custSeries, $custPrev] = $this->split(
            User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))
                ->where('created_at', '>=', $windowStart)->get(['created_at']),
            fn (User $u) => $u->created_at,
            fn () => 1,
            $keys, $unit, $curStart,
        );

        [$prodSeries, $prodPrev] = $this->split(
            Product::where('created_at', '>=', $windowStart)->get(['created_at']),
            fn (Product $p) => $p->created_at,
            fn () => 1,
            $keys, $unit, $curStart,
        );

        return [
            'range' => $range,
            'rangeLabel' => $rangeLabel,
            'ranges' => ['7d' => '7 days', '30d' => '30 days', '12m' => '12 months'],
            'kpis' => [
                $this->kpi('Revenue', array_sum($revSeries), true, $revSeries, array_sum($revSeries), $revPrev, 'fa-sack-dollar', 'blue', $rangeLabel),
                $this->kpi('Orders', array_sum($ordSeries), false, $ordSeries, array_sum($ordSeries), $ordPrev, 'fa-bag-shopping', 'orange', $rangeLabel),
                $this->kpi('Customers', $this->customerCount(), false, $custSeries, array_sum($custSeries), $custPrev, 'fa-users', 'green', 'total'),
                $this->kpi('Products', Product::count(), false, $prodSeries, array_sum($prodSeries), $prodPrev, 'fa-shirt', 'violet', 'total'),
            ],
            'chart' => $this->chart($buckets, $revSeries),
            'statusBreakdown' => $this->statusBreakdown(),
            'paymentBreakdown' => $this->paymentBreakdown(),
            'operations' => $this->operationsQueue(),
            'fulfillment' => $this->fulfillmentPulse($curStart),
            'topProducts' => $this->topProducts($curStart),
            'recentOrders' => $this->recentOrders(),
            'lowStock' => $this->lowStock(),
        ];
    }

    /**
     * Ordered buckets (oldest → newest) for the window.
     *
     * @return Collection<int, array{key: string, label: string, date: Carbon}>
     */
    private function buckets(string $unit, int $count): Collection
    {
        $now = Carbon::now();

        return collect(range($count - 1, 0))->map(function (int $back) use ($now, $unit) {
            $date = $unit === 'day'
                ? $now->copy()->startOfDay()->subDays($back)
                : $now->copy()->startOfMonth()->subMonths($back);

            return [
                'key' => $unit === 'day' ? $date->format('Y-m-d') : $date->format('Y-m'),
                'label' => $unit === 'day' ? $date->format('M j') : $date->format('M'),
                'date' => $date,
            ];
        })->values();
    }

    /**
     * Split rows into the current window's per-bucket series and the previous
     * window's total (everything before $curStart).
     *
     * @param  Collection<int, object>  $rows
     * @param  array<int, string>  $keys
     * @return array{0: array<int, float>, 1: float}
     */
    private function split(Collection $rows, callable $dateOf, callable $valueOf, array $keys, string $unit, Carbon $curStart): array
    {
        $series = array_fill_keys($keys, 0.0);
        $previous = 0.0;

        foreach ($rows as $row) {
            $date = $dateOf($row);
            $value = (float) $valueOf($row);

            if ($date >= $curStart) {
                $key = $unit === 'day' ? $date->format('Y-m-d') : $date->format('Y-m');
                if (isset($series[$key])) {
                    $series[$key] += $value;
                }
            } else {
                $previous += $value;
            }
        }

        return [array_values($series), $previous];
    }

    /**
     * @param  array<int, float>  $series
     * @return array<string, mixed>
     */
    private function kpi(string $label, float $raw, bool $isMoney, array $series, float $current, float $previous, string $icon, string $tone, string $sub): array
    {
        return [
            'label' => $label,
            'value' => $isMoney ? $this->money($raw) : number_format($raw),
            'raw' => $isMoney ? round($raw) : (int) $raw,
            'prefix' => $isMoney ? '$' : '',
            'sub' => $sub,
            'icon' => $icon,
            'tone' => $tone,
            'color' => self::TONE_COLOR[$tone] ?? '#2563eb',
            'series' => array_map(fn ($v) => round($v, 2), $series),
            ...$this->trend($current, $previous),
        ];
    }

    /**
     * @return array{trend: string, up: bool}
     */
    private function trend(float $current, float $previous): array
    {
        if ($previous <= 0.0) {
            $pct = $current > 0 ? 100.0 : 0.0;
        } else {
            $pct = (($current - $previous) / $previous) * 100;
        }

        return ['trend' => sprintf('%+.1f%%', $pct), 'up' => $pct >= 0];
    }

    /**
     * Raw revenue series for the ApexCharts area chart.
     *
     * @param  Collection<int, array{key: string, label: string, date: Carbon}>  $buckets
     * @param  array<int, float>  $series
     * @return array<string, mixed>
     */
    private function chart(Collection $buckets, array $series): array
    {
        return [
            'labels' => $buckets->pluck('label')->all(),
            'values' => array_map(fn ($v) => round($v, 2), array_values($series)),
            'total' => $this->money(array_sum($series)),
            'peak' => $this->money(max($series) ?: 0),
        ];
    }

    /**
     * Orders grouped by status (only those present), for the breakdown bar.
     *
     * @return array<int, array<string, mixed>>
     */
    private function statusBreakdown(): array
    {
        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $total = (int) $counts->sum();

        return collect(OrderStatus::cases())
            ->map(fn (OrderStatus $s) => [
                'label' => $s->label(),
                'count' => (int) ($counts[$s->value] ?? 0),
                'pct' => $total > 0 ? round(((int) ($counts[$s->value] ?? 0)) / $total * 100, 1) : 0,
                'color' => $s->color(),
            ])
            ->filter(fn ($row) => $row['count'] > 0)
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function paymentBreakdown(): array
    {
        $counts = Order::query()
            ->selectRaw('payment_status, COUNT(*) as c')
            ->groupBy('payment_status')
            ->pluck('c', 'payment_status');

        $colors = [
            PaymentStatus::Paid->value => '#10b981',
            PaymentStatus::Unpaid->value => '#f59e0b',
            PaymentStatus::PartiallyRefunded->value => '#0ea5e9',
            PaymentStatus::Refunded->value => '#ef4444',
        ];

        return collect(PaymentStatus::cases())
            ->map(fn (PaymentStatus $status) => [
                'label' => $status->label(),
                'count' => (int) ($counts[$status->value] ?? 0),
                'color' => $colors[$status->value],
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function operationsQueue(): array
    {
        $lowStockCount = Product::query()
            ->where('product_type', 'single')
            ->where('low_stock_alert', '>', 0)
            ->whereColumn('stock', '<=', 'low_stock_alert')
            ->count()
            + ProductVariant::query()
                ->where('low_stock_alert', '>', 0)
                ->whereColumn('stock', '<=', 'low_stock_alert')
                ->count();

        return [
            [
                'label' => 'Pending orders',
                'value' => Order::where('status', OrderStatus::Pending->value)->count(),
                'icon' => 'fa-receipt',
                'tone' => 'info',
                'url' => route('admin.orders.index', ['status' => OrderStatus::Pending->value]),
            ],
            [
                'label' => 'Unpaid orders',
                'value' => Order::where('payment_status', PaymentStatus::Unpaid->value)->count(),
                'icon' => 'fa-credit-card',
                'tone' => 'warning',
                'url' => route('admin.orders.index', ['payment_status' => PaymentStatus::Unpaid->value]),
            ],
            [
                'label' => 'Return requests',
                'value' => ReturnRequest::where('status', 'requested')->count(),
                'icon' => 'fa-rotate-left',
                'tone' => 'danger',
                'url' => route('admin.returns.index', ['status' => 'requested']),
            ],
            [
                'label' => 'Pending reviews',
                'value' => Review::where('status', ReviewStatus::Pending->value)->count(),
                'icon' => 'fa-star-half-stroke',
                'tone' => 'info',
                'url' => route('admin.reviews.index', ['status' => ReviewStatus::Pending->value]),
            ],
            [
                'label' => 'Stock alerts',
                'value' => $lowStockCount,
                'icon' => 'fa-box-open',
                'tone' => 'warning',
                'url' => route('admin.inventory.index'),
            ],
            [
                'label' => 'Abandoned carts',
                'value' => AbandonedCart::whereIn('status', ['new', 'contacted'])->count(),
                'icon' => 'fa-cart-arrow-down',
                'tone' => 'warning',
                'url' => route('admin.abandoned-carts.index'),
            ],
            [
                'label' => 'Unread alerts',
                'value' => AdminNotification::unread()->count(),
                'icon' => 'fa-bell',
                'tone' => 'danger',
                'url' => route('admin.notifications.index', ['state' => 'unread']),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fulfillmentPulse(Carbon $start): array
    {
        $open = Order::whereIn('status', [
            OrderStatus::Pending->value,
            OrderStatus::Paid->value,
            OrderStatus::Processing->value,
            OrderStatus::Shipped->value,
        ])->count();

        $shipped = Order::where('status', OrderStatus::Shipped->value)->count();
        $delivered = Order::where('status', OrderStatus::Delivered->value)->where('updated_at', '>=', $start)->count();
        $cancelled = Order::where('status', OrderStatus::Cancelled->value)->where('updated_at', '>=', $start)->count();
        $total = max(1, $open + $delivered + $cancelled);

        return [
            'open' => $open,
            'shipped' => $shipped,
            'delivered' => $delivered,
            'cancelled' => $cancelled,
            'health' => (int) max(0, min(100, round((($delivered + $shipped) / $total) * 100))),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function topProducts(Carbon $start, int $limit = 5): Collection
    {
        return OrderDetail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->where('orders.created_at', '>=', $start)
            ->selectRaw('order_details.product_id, order_details.name, order_details.sku, SUM(order_details.quantity) as sold, SUM(order_details.line_total) as revenue')
            ->groupBy('order_details.product_id', 'order_details.name', 'order_details.sku')
            ->orderByDesc('sold')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'sku' => $row->sku ?: '-',
                'sold' => (int) $row->sold,
                'revenue' => $this->money((float) $row->revenue),
                'pct' => min(100, max(8, (int) $row->sold * 8)),
            ]);
    }

    /**
     * @return Collection<int, Order>
     */
    private function recentOrders(int $limit = 6): Collection
    {
        return Order::with('user')->latest()->limit($limit)->get();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function lowStock(int $limit = 5): Collection
    {
        $singles = Product::query()
            ->where('product_type', 'single')
            ->where('low_stock_alert', '>', 0)
            ->whereColumn('stock', '<=', 'low_stock_alert')
            ->orderBy('stock')
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'stock', 'low_stock_alert'])
            ->map(fn (Product $p) => [
                'name' => $p->name,
                'sku' => $p->sku ?: '—',
                'stock' => (int) $p->stock,
                'pct' => $this->stockPct((int) $p->stock, (int) $p->low_stock_alert),
            ]);

        $variants = ProductVariant::query()
            ->with('product:id,name')
            ->where('low_stock_alert', '>', 0)
            ->whereColumn('stock', '<=', 'low_stock_alert')
            ->orderBy('stock')
            ->limit($limit)
            ->get()
            ->map(fn (ProductVariant $v) => [
                'name' => $v->product?->name ?? 'Product',
                'sku' => $v->sku ?: '—',
                'stock' => (int) $v->stock,
                'pct' => $this->stockPct((int) $v->stock, (int) $v->low_stock_alert),
            ]);

        return $singles->concat($variants)->sortBy('stock')->take($limit)->values();
    }

    private function stockPct(int $stock, int $alert): int
    {
        $alert = max(1, $alert);

        return (int) min(100, max(4, round(($stock / $alert) * 100)));
    }

    private function customerCount(): int
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))->count();
    }

    private function money(float $amount): string
    {
        return '$'.number_format($amount, 0);
    }
}
