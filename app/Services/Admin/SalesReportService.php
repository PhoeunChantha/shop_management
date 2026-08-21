<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ReturnRequest;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SalesReportService extends ReportService
{
    /**
     * Full analytics payload for the Sales dashboard. Every section honours the
     * same date / status / payment / customer filter set, so selecting a single
     * customer re-scopes the KPIs, chart, breakdowns and transactions together.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);
        [$prevStart, $prevEnd] = $this->previousRange($start, $end);

        $summary = $this->summaryFor($start, $end, $filters);
        $previous = $this->summaryFor($prevStart, $prevEnd, $filters);

        return [
            'filters' => $this->appliedFilters($start, $end, $filters),
            'summary' => $summary,
            'comparison' => $this->comparison($summary, $previous, ['total_revenue', 'net_revenue', 'orders', 'gross_profit']),
            'previousRange' => [
                'start_date' => $prevStart->toDateString(),
                'end_date' => $prevEnd->toDateString(),
            ],
            'series' => $this->dailySeries($start, $end, $filters),
            'salesByDay' => $this->salesByDay($start, $end, $filters),
            'categories' => $this->categoryBreakdown($start, $end, $filters),
            'paymentMethods' => $this->paymentMethods($start, $end, $filters),
            'topProducts' => $this->topProducts($start, $end, $filters),
            'topCustomers' => $this->topCustomers($start, $end, $filters),
            'refunds' => $this->refundStats($start, $end, $filters, (int) $summary['orders']),
            'transactions' => $this->transactions($start, $end, $filters),
        ];
    }

    /**
     * Summary KPIs for an arbitrary window — reused for the current and prior
     * period so the comparison deltas share one definition of every metric.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    private function summaryFor(CarbonImmutable $start, CarbonImmutable $end, array $filters): array
    {
        $orders = $this->ordersBetween($start, $end, $filters);
        $paidOrders = (clone $orders)->whereIn('payment_status', $this->paidStatuses());

        $merchandise = (float) (clone $paidOrders)->sum('subtotal');
        $discounts = (float) (clone $paidOrders)->sum('discount_total');
        $totalRevenue = (float) (clone $paidOrders)->sum('grand_total');
        $paidCount = (clone $paidOrders)->count();
        $netSales = $merchandise - $discounts;

        $cogs = (float) $this->applyOrderFilters(
            OrderDetail::query()->join('orders', 'orders.id', '=', 'order_details.order_id'),
            $filters,
        )
            ->whereBetween(DB::raw('DATE(COALESCE(orders.placed_at, orders.created_at))'), [$start->toDateString(), $end->toDateString()])
            ->whereIn('orders.payment_status', $this->paidStatuses())
            ->sum(DB::raw('COALESCE(order_details.unit_cost, 0) * order_details.quantity'));

        $refunds = $this->refundStats($start, $end, $filters, (clone $orders)->count());

        return [
            'gross_sales' => $merchandise,
            'discount_total' => $discounts,
            'net_sales' => $netSales,
            'tax_total' => (float) (clone $paidOrders)->sum('tax_total'),
            'shipping_total' => (float) (clone $paidOrders)->sum('shipping_total'),
            'total_revenue' => $totalRevenue,
            'refunds' => $refunds['amount'],
            'net_revenue' => $totalRevenue - $refunds['amount'],
            'cogs' => $cogs,
            'gross_profit' => $netSales - $cogs,
            'margin' => $netSales > 0 ? round((($netSales - $cogs) / $netSales) * 100, 1) : 0.0,
            'orders' => (clone $orders)->count(),
            'paid_orders' => $paidCount,
            'average_order' => $paidCount > 0 ? $totalRevenue / $paidCount : 0.0,
        ];
    }

    /**
     * Equal-length window immediately before [$start, $end] for period comparison.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function previousRange(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $days = (int) $start->startOfDay()->diffInDays($end->startOfDay()) + 1;
        $prevEnd = $start->subDay()->endOfDay();
        $prevStart = $prevEnd->subDays($days - 1)->startOfDay();

        return [$prevStart, $prevEnd];
    }

    /**
     * Percentage change per metric. change=null when the prior period is zero —
     * we never fabricate a percentage against a zero base.
     *
     * @param  array<string, float|int>  $current
     * @param  array<string, float|int>  $previous
     * @param  array<int, string>  $keys
     * @return array<string, array{previous: float, change: float|null, direction: string}>
     */
    private function comparison(array $current, array $previous, array $keys): array
    {
        $out = [];

        foreach ($keys as $key) {
            $cur = (float) ($current[$key] ?? 0);
            $prev = (float) ($previous[$key] ?? 0);
            $change = $prev > 0 ? round((($cur - $prev) / $prev) * 100, 1) : null;

            $out[$key] = [
                'previous' => $prev,
                'change' => $change,
                'direction' => $change === null ? 'flat' : ($change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat')),
            ];
        }

        return $out;
    }

    /**
     * Continuous per-day series (gap-filled) powering the performance chart:
     * revenue (collected), order count and gross profit for each day in range.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, array<int, string|float|int>>
     */
    private function dailySeries(CarbonImmutable $start, CarbonImmutable $end, array $filters): array
    {
        $orders = $this->ordersBetween($start, $end, $filters)
            ->whereIn('payment_status', $this->paidStatuses())
            ->selectRaw('DATE(COALESCE(placed_at, created_at)) as d')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(grand_total) as revenue')
            ->selectRaw('SUM(subtotal - discount_total) as net_sales')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $cogs = $this->applyOrderFilters(
            OrderDetail::query()->join('orders', 'orders.id', '=', 'order_details.order_id'),
            $filters,
        )
            ->whereBetween(DB::raw('DATE(COALESCE(orders.placed_at, orders.created_at))'), [$start->toDateString(), $end->toDateString()])
            ->whereIn('orders.payment_status', $this->paidStatuses())
            ->selectRaw('DATE(COALESCE(orders.placed_at, orders.created_at)) as d')
            ->selectRaw('SUM(COALESCE(order_details.unit_cost, 0) * order_details.quantity) as cogs')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $labels = [];
        $revenue = [];
        $ordersOut = [];
        $profit = [];

        $cursor = $start->startOfDay();
        $last = $end->startOfDay();

        // Cap the drawn points so a huge custom range never renders thousands of
        // ticks; day granularity is fine for any range up to ~13 months.
        while ($cursor->lessThanOrEqualTo($last) && count($labels) <= 400) {
            $key = $cursor->toDateString();
            $row = $orders->get($key);
            $net = (float) ($row->net_sales ?? 0);
            $dayCogs = (float) ($cogs->get($key)->cogs ?? 0);

            $labels[] = $key;
            $revenue[] = round((float) ($row->revenue ?? 0), 2);
            $ordersOut[] = (int) ($row->orders ?? 0);
            $profit[] = round($net - $dayCogs, 2);

            $cursor = $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'orders' => $ordersOut,
            'profit' => $profit,
        ];
    }

    /**
     * Daily rollup kept for the compact "Daily performance" table.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function salesByDay(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return $this->ordersBetween($start, $end, $filters)
            ->whereIn('payment_status', $this->paidStatuses())
            ->selectRaw('DATE(COALESCE(placed_at, created_at)) as date')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(grand_total) as gross_sales')
            ->selectRaw('SUM(discount_total) as discounts')
            ->selectRaw('SUM(subtotal - discount_total) as net_sales')
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(60)
            ->get()
            ->map(fn (object $row): array => [
                'date' => (string) $row->date,
                'orders' => (int) $row->orders,
                'gross_sales' => (float) $row->gross_sales,
                'discounts' => (float) $row->discounts,
                'net_sales' => (float) $row->net_sales,
            ]);
    }

    /**
     * Revenue / units share per product category (paid orders in range).
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function categoryBreakdown(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        $rows = $this->applyOrderFilters(
            OrderDetail::query()
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->leftJoin('products', 'products.id', '=', 'order_details.product_id')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id'),
            $filters,
        )
            ->whereBetween(DB::raw('DATE(COALESCE(orders.placed_at, orders.created_at))'), [$start->toDateString(), $end->toDateString()])
            ->whereIn('orders.payment_status', $this->paidStatuses())
            ->selectRaw("COALESCE(categories.name, 'Uncategorized') as category")
            ->selectRaw('COUNT(DISTINCT order_details.order_id) as orders')
            ->selectRaw('SUM(order_details.quantity) as units')
            ->selectRaw('SUM(order_details.line_total) as revenue')
            ->groupBy('category')
            ->orderByDesc('revenue')
            ->get();

        $total = (float) $rows->sum('revenue');

        return $rows->take(8)->map(fn (object $row): array => [
            'category' => (string) $row->category,
            'orders' => (int) $row->orders,
            'units' => (int) $row->units,
            'revenue' => (float) $row->revenue,
            'percent' => $total > 0 ? round(((float) $row->revenue / $total) * 100, 1) : 0.0,
        ]);
    }

    /**
     * Orders / revenue share per payment method (paid orders in range).
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function paymentMethods(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        $rows = $this->ordersBetween($start, $end, $filters)
            ->whereIn('payment_status', $this->paidStatuses())
            ->selectRaw("COALESCE(NULLIF(payment_method, ''), 'other') as method")
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(grand_total) as revenue')
            ->groupBy('method')
            ->orderByDesc('revenue')
            ->get();

        $total = (float) $rows->sum('revenue');

        return $rows->map(fn (object $row): array => [
            'method' => $this->methodLabel((string) $row->method),
            'orders' => (int) $row->orders,
            'revenue' => (float) $row->revenue,
            'percent' => $total > 0 ? round(((float) $row->revenue / $total) * 100, 1) : 0.0,
        ]);
    }

    private function methodLabel(string $method): string
    {
        return match (mb_strtolower($method)) {
            'aba', 'aba_payway', 'payway' => 'ABA PayWay',
            'wallet' => 'Wallet',
            'cash', 'cod' => 'Cash',
            'card', 'credit_card' => 'Credit Card',
            'qr' => 'QR Payment',
            'other', '' => 'Other',
            default => ucwords(str_replace('_', ' ', $method)),
        };
    }

    /**
     * Best sellers by revenue, with unit/order counts, profit and the order-line
     * image snapshot for thumbnails.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function topProducts(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return $this->applyOrderFilters(
            OrderDetail::query()->join('orders', 'orders.id', '=', 'order_details.order_id'),
            $filters,
        )
            ->whereBetween(DB::raw('DATE(COALESCE(orders.placed_at, orders.created_at))'), [$start->toDateString(), $end->toDateString()])
            ->whereIn('orders.payment_status', $this->paidStatuses())
            ->selectRaw('order_details.name')
            ->selectRaw("COALESCE(order_details.sku, '') as sku")
            ->selectRaw('MAX(order_details.image) as image')
            ->selectRaw('COUNT(DISTINCT order_details.order_id) as orders')
            ->selectRaw('SUM(order_details.quantity) as quantity')
            ->selectRaw('SUM(order_details.line_total) as revenue')
            ->selectRaw('SUM(order_details.line_total - COALESCE(order_details.unit_cost, 0) * order_details.quantity) as profit')
            ->groupBy('order_details.name', 'order_details.sku')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn (object $row): array => [
                'name' => (string) $row->name,
                'sku' => (string) $row->sku,
                'image' => $row->image !== null ? (string) $row->image : null,
                'orders' => (int) $row->orders,
                'quantity' => (int) $row->quantity,
                'revenue' => (float) $row->revenue,
                'profit' => (float) $row->profit,
            ]);
    }

    /**
     * Highest-value customers (paid orders), with items, profit and last order.
     * Grouped on the denormalised order snapshot so guests are included.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float|null>>
     */
    private function topCustomers(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        $lineAgg = DB::table('order_details')
            ->select('order_id')
            ->selectRaw('SUM(quantity) as items')
            ->selectRaw('SUM(COALESCE(unit_cost, 0) * quantity) as cogs')
            ->groupBy('order_id');

        return $this->applyOrderFilters(Order::query()->from('orders'), $filters)
            ->leftJoinSub($lineAgg, 'la', 'la.order_id', '=', 'orders.id')
            ->whereBetween(DB::raw('DATE(COALESCE(orders.placed_at, orders.created_at))'), [$start->toDateString(), $end->toDateString()])
            ->whereIn('orders.payment_status', $this->paidStatuses())
            ->selectRaw('orders.customer_name, orders.customer_email')
            ->selectRaw('MAX(orders.user_id) as user_id')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(COALESCE(la.items, 0)) as items')
            ->selectRaw('SUM(orders.grand_total) as spend')
            ->selectRaw('SUM((orders.subtotal - orders.discount_total) - COALESCE(la.cogs, 0)) as profit')
            ->selectRaw('MAX(COALESCE(orders.placed_at, orders.created_at)) as last_order')
            ->groupBy('orders.customer_name', 'orders.customer_email')
            ->orderByDesc('spend')
            ->limit(8)
            ->get()
            ->map(fn (object $row): array => [
                'customer_name' => $this->customerName((string) ($row->customer_name ?? ''), $row->user_id),
                'customer_email' => (string) ($row->customer_email ?? ''),
                'is_guest' => $row->user_id === null,
                'orders' => (int) $row->orders,
                'items' => (int) $row->items,
                'spend' => (float) $row->spend,
                'profit' => (float) $row->profit,
                'last_order' => $row->last_order ? (string) $row->last_order : null,
            ]);
    }

    /**
     * Refund amount, refunded-order count and refund rate — customer-aware via a
     * join to the parent order.
     *
     * @param  array<string, mixed>  $filters
     * @return array{amount: float, orders: int, rate: float}
     */
    private function refundStats(CarbonImmutable $start, CarbonImmutable $end, array $filters, int $orderCount): array
    {
        $base = ReturnRequest::query()
            ->join('orders', 'orders.id', '=', 'return_requests.order_id')
            ->whereIn('return_requests.refund_status', ['partial', 'refunded'])
            ->whereBetween(DB::raw('DATE(COALESCE(return_requests.refunded_at, return_requests.updated_at))'), [$start->toDateString(), $end->toDateString()])
            ->when(filled($filters['customer'] ?? null), fn (Builder $q) => $q->where(function (Builder $inner) use ($filters) {
                $inner->where('orders.customer_email', $filters['customer'])->orWhere('orders.customer_name', $filters['customer']);
            }));

        $amount = (float) (clone $base)->sum('return_requests.refund_amount');
        $refundedOrders = (int) (clone $base)->distinct('return_requests.order_id')->count('return_requests.order_id');

        return [
            'amount' => $amount,
            'orders' => $refundedOrders,
            'rate' => $orderCount > 0 ? round(($refundedOrders / $orderCount) * 100, 1) : 0.0,
        ];
    }

    /**
     * Order-level transactions table — the source of truth behind the summary.
     * DB-paginated, searchable and sortable, every column derived from the order
     * snapshot plus per-order line (items, COGS) and refund rollups.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function transactions(CarbonImmutable $start, CarbonImmutable $end, array $filters): LengthAwarePaginator
    {
        $lineAgg = DB::table('order_details')
            ->select('order_id')
            ->selectRaw('SUM(quantity) as items')
            ->selectRaw('SUM(COALESCE(unit_cost, 0) * quantity) as cogs')
            ->groupBy('order_id');

        $refundAgg = DB::table('return_requests')
            ->select('order_id')
            ->whereIn('refund_status', ['partial', 'refunded'])
            ->selectRaw('SUM(refund_amount) as refund')
            ->groupBy('order_id');

        $sortMap = [
            'date' => DB::raw('COALESCE(orders.placed_at, orders.created_at)'),
            'gross' => 'orders.subtotal',
            'net' => 'orders.grand_total',
        ];
        $sort = (string) ($filters['sort'] ?? 'date');
        $column = $sortMap[$sort] ?? $sortMap['date'];
        $direction = mb_strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) ($filters['search'] ?? ''));

        $query = $this->applyOrderFilters(Order::query()->from('orders'), $filters)
            ->leftJoinSub($lineAgg, 'la', 'la.order_id', '=', 'orders.id')
            ->leftJoinSub($refundAgg, 'ra', 'ra.order_id', '=', 'orders.id')
            ->whereBetween(DB::raw('DATE(COALESCE(orders.placed_at, orders.created_at))'), [$start->toDateString(), $end->toDateString()])
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $inner) use ($search) {
                $inner->where('orders.order_number', 'like', "%{$search}%")
                    ->orWhere('orders.customer_name', 'like', "%{$search}%")
                    ->orWhere('orders.customer_email', 'like', "%{$search}%");
            }))
            ->select('orders.*')
            ->selectRaw('COALESCE(la.items, 0) as items_count')
            ->selectRaw('COALESCE(la.cogs, 0) as cogs_total')
            ->selectRaw('COALESCE(ra.refund, 0) as refund_total')
            ->orderBy($column, $direction);

        $perPage = $this->perPage($filters, 25);

        return $query->paginate($perPage)->withQueryString()->through(function (Order $order): array {
            $gross = (float) $order->subtotal;
            $discount = (float) $order->discount_total;
            $refund = (float) ($order->refund_total ?? 0);
            $cogs = (float) ($order->cogs_total ?? 0);
            $netSales = $gross - $discount - $refund;

            return [
                'date' => optional($order->placed_at ?? $order->created_at)->format('M d, Y'),
                'order_number' => $order->order_number,
                'order_id' => $order->id,
                'customer_name' => $this->customerName((string) ($order->customer_name ?? ''), $order->user_id),
                'customer_email' => (string) ($order->customer_email ?? ''),
                'customer_phone' => (string) ($order->customer_phone ?? ''),
                'is_guest' => $order->user_id === null,
                'items' => (int) ($order->items_count ?? 0),
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'gross' => $gross,
                'discount' => $discount,
                'refund' => $refund,
                'net_sales' => $netSales,
                'profit' => ($gross - $discount) - $cogs,
            ];
        });
    }

    private function customerName(string $name, ?int $userId): string
    {
        if ($name !== '') {
            return $name;
        }

        return $userId === null ? 'Guest Customer' : 'Registered Customer';
    }

    /**
     * Server-side customer autocomplete: distinct order customers matched on name
     * or email. Never loads the full customer base into the browser.
     *
     * @return array<int, array{value: string, name: string, email: string}>
     */
    public function customerOptions(string $term, int $limit = 20): array
    {
        return Order::query()
            ->whereNotNull('customer_email')
            ->where('customer_email', '!=', '')
            ->when($term !== '', fn (Builder $q) => $q->where(function (Builder $inner) use ($term) {
                $inner->where('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%");
            }))
            ->selectRaw('customer_name, customer_email')
            ->selectRaw('COUNT(*) as orders')
            ->groupBy('customer_name', 'customer_email')
            ->orderByDesc('orders')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'value' => (string) $row->customer_email,
                'name' => (string) ($row->customer_name ?: 'Guest Customer'),
                'email' => (string) $row->customer_email,
            ])
            ->all();
    }

    /**
     * Order-level export rows (respects every active filter, incl. customer).
     * Header row first, per the CSV/PDF streaming contract.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);

        // Reuse the transactions query but export the whole result set (no paging).
        $filters['per_page'] = 100000;
        $rows = $this->transactions($start, $end, $filters)->items();

        $out = [[
            'Date', 'Order', 'Customer', 'Email', 'Phone', 'Status', 'Payment',
            'Gross Sales', 'Discount', 'Refund', 'Net Sales', 'Profit',
        ]];

        foreach ($rows as $row) {
            $out[] = [
                $row['date'],
                $row['order_number'],
                $row['customer_name'],
                $row['customer_email'],
                $row['customer_phone'],
                $row['status']->label(),
                $row['payment_status']->label(),
                number_format($row['gross'], 2, '.', ''),
                number_format($row['discount'], 2, '.', ''),
                number_format($row['refund'], 2, '.', ''),
                number_format($row['net_sales'], 2, '.', ''),
                number_format($row['profit'], 2, '.', ''),
            ];
        }

        return $out;
    }
}
