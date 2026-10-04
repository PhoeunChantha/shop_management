<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReportPreset;
use App\Enums\ReviewStatus;
use App\Models\AbandonedCart;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\User;
use App\Services\Admin\Reports\Comparison;
use App\Services\Admin\Reports\ReportFilters;
use App\Services\Admin\Reports\ReportSeries;
use App\Services\Admin\Reports\SalesLedger;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Aggregates the admin dashboard for an arbitrary date range (start–end):
 * windowed KPI cards with trend, a revenue area chart, an orders-by-status
 * breakdown, recent orders and low-stock items.
 *
 * Revenue, paid orders and top products come from the shared SalesLedger
 * (same definition as the Overview and Sales reports); buckets are daily for
 * spans up to ~3 months, monthly beyond.
 */
final class DashboardService
{
    public function __construct(private readonly SalesLedger $ledger) {}

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
    public function overview(?string $from = null, ?string $to = null): array
    {
        [$start, $end] = $this->resolveRange($from, $to);
        $filters = new ReportFilters($start, $end, ReportPreset::Custom);
        $previous = $filters->previous();
        $unit = ReportSeries::unit($filters);
        $count = count(ReportSeries::buckets($filters, $unit));

        $rangeShort = $count.' '.Str::plural($unit, $count);

        // Revenue and paid orders come from the shared sales ledger, so the
        // dashboard matches the Overview and Sales reports to the cent.
        $sales = $this->ledger->summary($filters);
        $prior = $this->ledger->summary($previous);
        $series = $this->ledger->series($filters, $unit);

        $newCustomers = fn (ReportFilters $f) => $this->customerQuery()->whereBetween('created_at', [$f->start, $f->end])->count();
        $newProducts = fn (ReportFilters $f) => Product::query()->whereBetween('created_at', [$f->start, $f->end])->count();

        return [
            'dateFrom' => $start->format('Y-m-d'),
            'dateTo' => $end->format('Y-m-d'),
            'rangeLabel' => $this->rangeLabel($start, $end),
            'sales' => $sales,
            'kpis' => [
                $this->kpi('Revenue', $sales['total_sales'], true, $prior['total_sales'], 'fa-sack-dollar', 'blue', $rangeShort),
                $this->kpi('Orders', $sales['orders'], false, $prior['orders'], 'fa-bag-shopping', 'orange', __('paid').' · '.$rangeShort),
                $this->kpi('Customers', $this->customerQuery()->count(), false, null, 'fa-users', 'green', 'total', $newCustomers($filters), $newCustomers($previous)),
                $this->kpi('Products', Product::count(), false, null, 'fa-shirt', 'violet', 'total', $newProducts($filters), $newProducts($previous)),
            ],
            'chart' => $this->chart($series),
            'statusBreakdown' => $this->statusBreakdown(),
            'operations' => $this->operationsQueue(),
            'topProducts' => $this->topProducts($filters),
            'recentOrders' => $this->recentOrders(),
            'lowStock' => $this->lowStock(),
        ];
    }

    /**
     * Parse the requested from/to into an ordered [start-of-day, end-of-day] pair,
     * defaulting to the last 30 days when either bound is missing.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveRange(?string $from, ?string $to): array
    {
        $end = $to ? CarbonImmutable::parse($to)->endOfDay() : CarbonImmutable::now()->endOfDay();
        $start = $from ? CarbonImmutable::parse($from)->startOfDay() : $end->subDays(29)->startOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return [$start, $end];
    }

    /** Human-readable label for the selected range, e.g. "Jun 25 – Jul 24, 2026". */
    private function rangeLabel(CarbonImmutable $start, CarbonImmutable $end): string
    {
        if ($start->isSameDay($end)) {
            return $start->format('M j, Y');
        }

        return $start->format('Y') === $end->format('Y')
            ? $start->format('M j').' – '.$end->format('M j, Y')
            : $start->format('M j, Y').' – '.$end->format('M j, Y');
    }

    /**
     * A KPI card. The trend compares the window with the previous one: the
     * value itself for windowed metrics, or new additions ($current/$previous)
     * for all-time totals (customers, products).
     *
     * @return array<string, mixed>
     */
    private function kpi(string $label, float|int $raw, bool $isMoney, float|int|null $previousValue, string $icon, string $tone, string $sub, ?int $current = null, ?int $previous = null): array
    {
        $trend = $previousValue !== null
            ? Comparison::of($raw, $previousValue)
            : Comparison::of($current ?? 0, $previous ?? 0);

        return [
            'label' => $label,
            'value' => $isMoney ? $this->money((float) $raw) : number_format($raw),
            'raw' => $isMoney ? round((float) $raw, 2) : (int) $raw,
            'prefix' => $isMoney ? '$' : '',
            'sub' => $sub,
            'icon' => $icon,
            'tone' => $tone,
            'color' => self::TONE_COLOR[$tone] ?? '#2563eb',
            // null change = no prior base: shown as "—", never a fabricated +100%.
            'trend' => $trend['change'] === null ? '—' : sprintf('%+.1f%%', $trend['change']),
            'direction' => $trend['direction'],
            'up' => $trend['direction'] === 'up',
        ];
    }

    /**
     * Total sales per bucket for the ApexCharts area chart.
     *
     * @param  array<int, array<string, mixed>>  $series
     * @return array<string, mixed>
     */
    private function chart(array $series): array
    {
        $values = array_map(fn (array $row) => round((float) $row['total_sales'], 2), $series);

        return [
            'labels' => array_column($series, 'label'),
            'values' => $values,
            'total' => $this->money(array_sum($values)),
            'peak' => $this->money($values ? max($values) : 0),
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
                'label' => 'Unfulfilled orders',
                'value' => Order::where('fulfillment_status', FulfillmentStatus::Unfulfilled->value)->count(),
                'icon' => 'fa-box-open',
                'tone' => 'info',
                'url' => route('admin.orders.index', ['fulfillment_status' => FulfillmentStatus::Unfulfilled->value]),
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
     * Best sellers by units on paid orders in the window (shared ledger).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function topProducts(ReportFilters $filters, int $limit = 5): Collection
    {
        return $this->ledger->groupedLines($filters, 'x.product_id')
            ->orderByDesc('quantity')
            ->orderByDesc('net_sales')
            ->limit($limit)
            ->get()
            ->map(fn (object $row) => [
                'name' => $row->group_key === null ? __('Deleted products') : (string) $row->name,
                'sold' => (int) $row->quantity,
                'revenue' => $this->money((float) $row->net_sales),
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
            ->toBase()
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
            ->toBase()
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

    /** Registered customer accounts. */
    private function customerQuery(): Builder
    {
        return User::query()->whereHas('roles', fn ($q) => $q->where('name', 'customer'));
    }

    private function money(float $amount): string
    {
        return '$'.number_format($amount, 0);
    }
}
