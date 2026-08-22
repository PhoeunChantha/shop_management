<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PurchaseOrder;
use App\Models\ReturnRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FinanceReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);
        [$prevStart, $prevEnd] = $this->previousRange($start, $end);

        $summary = $this->summaryFor($start, $end, $filters);
        $previous = $this->summaryFor($prevStart, $prevEnd, $filters);

        return [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => $filters['status'] ?? null,
                'payment_status' => $filters['payment_status'] ?? null,
            ],
            'summary' => $summary,
            // Period-over-period change vs the equal-length window before this one.
            'comparison' => $this->comparison($summary, $previous, ['total_revenue', 'net_revenue', 'orders', 'average_order', 'gross_profit']),
            'previousRange' => [
                'start_date' => $prevStart->toDateString(),
                'end_date' => $prevEnd->toDateString(),
            ],
            // Day-by-day series for the trend chart, aligned with the previous
            // period so the ghost line compares day N against day N.
            'chart' => $this->chartSeries($start, $end, $prevStart, $prevEnd, $filters),
            'topProducts' => $this->topProducts($start, $end, $filters, 5),
            'paymentMix' => $this->paymentMix($start, $end, $filters),
        ];
    }

    /**
     * Zero-filled daily series across the full range (days without sales must
     * still appear so the chart's x-axis is continuous), plus the previous
     * period's revenue aligned by day offset for the comparison ghost line.
     *
     * @param  array<string, mixed>  $filters
     * @return array{labels: array<int, string>, revenue: array<int, float>, orders: array<int, int>, prevRevenue: array<int, float>}
     */
    private function chartSeries(CarbonImmutable $start, CarbonImmutable $end, CarbonImmutable $prevStart, CarbonImmutable $prevEnd, array $filters): array
    {
        $current = $this->salesByDay($start, $end, $filters)->keyBy('date');
        $previous = $this->salesByDay($prevStart, $prevEnd, $filters)->keyBy('date');

        $labels = $revenue = $orders = $prevRevenue = [];
        $days = (int) $start->startOfDay()->diffInDays($end->startOfDay());

        for ($i = 0; $i <= $days; $i++) {
            $day = $start->addDays($i)->toDateString();
            $prevDay = $prevStart->addDays($i)->toDateString();

            $labels[] = $day;
            $revenue[] = (float) ($current[$day]['gross_sales'] ?? 0);
            $orders[] = (int) ($current[$day]['orders'] ?? 0);
            $prevRevenue[] = (float) ($previous[$prevDay]['gross_sales'] ?? 0);
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'orders' => $orders, 'prevRevenue' => $prevRevenue];
    }

    /**
     * Summary KPIs for an arbitrary window (reused for the current + prior period).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    private function summaryFor(CarbonImmutable $start, CarbonImmutable $end, array $filters): array
    {
        $orders = $this->ordersBetween($start, $end, $filters);
        $paidStatuses = [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value, PaymentStatus::Refunded->value];

        // Real commerce revenue chain: subtotal is merchandise BEFORE discount,
        // grand_total is what was actually collected (incl. tax + shipping).
        // One aggregate query instead of six separate sums/counts.
        $paid = (clone $orders)->whereIn('payment_status', $paidStatuses)
            ->selectRaw('COALESCE(SUM(subtotal), 0) as merchandise')
            ->selectRaw('COALESCE(SUM(discount_total), 0) as discounts')
            ->selectRaw('COALESCE(SUM(tax_total), 0) as tax')
            ->selectRaw('COALESCE(SUM(shipping_total), 0) as shipping')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total_revenue')
            ->selectRaw('COUNT(*) as paid_count')
            ->first();

        $merchandise = (float) $paid->merchandise;
        $discounts = (float) $paid->discounts;
        $tax = (float) $paid->tax;
        $shipping = (float) $paid->shipping;
        $totalRevenue = (float) $paid->total_revenue;
        $paidCount = (int) $paid->paid_count;
        $orderCount = (clone $orders)->count();

        // COGS from the per-line cost snapshot on paid orders in the window.
        $cogs = (float) OrderDetail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereBetween(DB::raw('DATE(COALESCE(orders.placed_at, orders.created_at))'), [$start->toDateString(), $end->toDateString()])
            ->whereIn('orders.payment_status', $paidStatuses)
            ->when(filled($filters['status'] ?? null), fn (Builder $q) => $q->where('orders.status', $filters['status']))
            ->when(filled($filters['payment_status'] ?? null), fn (Builder $q) => $q->where('orders.payment_status', $filters['payment_status']))
            ->sum(DB::raw('COALESCE(order_details.unit_cost, 0) * order_details.quantity'));

        $refunds = (float) ReturnRequest::query()
            ->whereIn('refund_status', ['partial', 'refunded'])
            ->whereBetween(DB::raw('DATE(COALESCE(refunded_at, updated_at))'), [$start->toDateString(), $end->toDateString()])
            ->sum('refund_amount');

        // Apply the same order filters so return_rate compares like with like
        // when a status/payment filter narrows the order count.
        $returnsCount = (int) ReturnRequest::query()
            ->whereBetween('requested_at', [$start->startOfDay(), $end->endOfDay()])
            ->when(filled($filters['status'] ?? null), fn (Builder $q) => $q->whereHas('order', fn (Builder $o) => $o->where('status', $filters['status'])))
            ->when(filled($filters['payment_status'] ?? null), fn (Builder $q) => $q->whereHas('order', fn (Builder $o) => $o->where('payment_status', $filters['payment_status'])))
            ->count();

        $purchaseCost = (float) PurchaseOrder::query()
            ->whereIn('status', ['ordered', 'partial', 'received'])
            ->whereBetween(DB::raw('DATE(COALESCE(ordered_at, created_at))'), [$start->toDateString(), $end->toDateString()])
            ->sum('subtotal');

        return [
            'gross_sales' => $merchandise,          // merchandise before discounts
            'discount_total' => $discounts,
            'net_sales' => $merchandise - $discounts, // after discounts, before tax/shipping
            'tax_total' => $tax,
            'shipping_total' => $shipping,
            'total_revenue' => $totalRevenue,        // collected incl. tax + shipping
            'refunds' => $refunds,
            'net_revenue' => $totalRevenue - $refunds,
            'cogs' => $cogs,
            'gross_profit' => ($merchandise - $discounts) - $cogs, // net sales − COGS
            'margin' => ($merchandise - $discounts) > 0 ? round(((($merchandise - $discounts) - $cogs) / ($merchandise - $discounts)) * 100, 1) : 0.0,
            'orders' => $orderCount,
            'paid_orders' => $paidCount,
            'average_order' => $paidCount > 0 ? $totalRevenue / $paidCount : 0.0,
            'return_rate' => $orderCount > 0 ? round(($returnsCount / $orderCount) * 100, 1) : 0.0,
            'purchase_cost' => $purchaseCost,
        ];
    }

    /**
     * The equal-length window immediately preceding [$start, $end].
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function previousRange(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $days = $start->startOfDay()->diffInDays($end->startOfDay()) + 1;
        $prevEnd = $start->subDay()->endOfDay();
        $prevStart = $prevEnd->subDays($days - 1)->startOfDay();

        return [$prevStart, $prevEnd];
    }

    /**
     * Percentage change per metric. Returns change=null when the prior period has
     * no base value — we never fabricate a percentage against zero.
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
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(string $type, array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);

        return match ($type) {
            'sales' => $this->salesByDay($start, $end, $filters)
                ->prepend(['Date' => 'Date', 'Orders' => 'Orders', 'Gross Sales' => 'Gross Sales', 'Tax' => 'Tax', 'Shipping' => 'Shipping', 'Discounts' => 'Discounts'])
                ->map(fn ($row) => array_values($row))
                ->all(),
            'products' => $this->topProducts($start, $end, $filters)
                ->prepend(['Product' => 'Product', 'SKU' => 'SKU', 'Quantity' => 'Quantity', 'Revenue' => 'Revenue'])
                ->map(fn ($row) => array_values($row))
                ->all(),
            'customers' => $this->customerSpend($start, $end, $filters)
                ->prepend(['Customer' => 'Customer', 'Email' => 'Email', 'Orders' => 'Orders', 'Spend' => 'Spend'])
                ->map(fn ($row) => array_values($row))
                ->all(),
            'purchases' => $this->purchaseOrders($start, $end)
                ->prepend(['PO Number' => 'PO Number', 'Supplier' => 'Supplier', 'Status' => 'Status', 'Ordered Date' => 'Ordered Date', 'Subtotal' => 'Subtotal'])
                ->map(fn ($row) => array_values($row))
                ->all(),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function dateRange(array $filters): array
    {
        $end = filled($filters['end_date'] ?? null)
            ? CarbonImmutable::parse((string) $filters['end_date'])->endOfDay()
            : now()->toImmutable()->endOfDay();

        $start = filled($filters['start_date'] ?? null)
            ? CarbonImmutable::parse((string) $filters['start_date'])->startOfDay()
            : $end->subDays(29)->startOfDay();

        if ($start->greaterThan($end)) {
            return [$end->startOfDay(), $start->endOfDay()];
        }

        return [$start, $end];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function ordersBetween(CarbonImmutable $start, CarbonImmutable $end, array $filters): Builder
    {
        return Order::query()
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['payment_status'] ?? null), fn (Builder $query) => $query->where('payment_status', $filters['payment_status']))
            ->whereBetween(DB::raw('DATE(COALESCE(placed_at, created_at))'), [$start->toDateString(), $end->toDateString()]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function salesByDay(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return $this->ordersBetween($start, $end, $filters)
            ->whereIn('payment_status', [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value, PaymentStatus::Refunded->value])
            ->selectRaw('DATE(COALESCE(placed_at, created_at)) as date')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(grand_total) as gross_sales')
            ->selectRaw('SUM(tax_total) as tax')
            ->selectRaw('SUM(shipping_total) as shipping')
            ->selectRaw('SUM(discount_total) as discounts')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn (object $row): array => [
                'date' => (string) $row->date,
                'orders' => (int) $row->orders,
                'gross_sales' => (float) $row->gross_sales,
                'tax' => (float) $row->tax,
                'shipping' => (float) $row->shipping,
                'discounts' => (float) $row->discounts,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function topProducts(CarbonImmutable $start, CarbonImmutable $end, array $filters, int $limit = 8): Collection
    {
        return OrderDetail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereBetween(DB::raw('DATE(COALESCE(orders.placed_at, orders.created_at))'), [$start->toDateString(), $end->toDateString()])
            ->whereIn('orders.payment_status', [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value, PaymentStatus::Refunded->value])
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('orders.status', $filters['status']))
            ->when(filled($filters['payment_status'] ?? null), fn ($query) => $query->where('orders.payment_status', $filters['payment_status']))
            ->selectRaw('order_details.name, COALESCE(order_details.sku, "") as sku')
            ->selectRaw('SUM(order_details.quantity) as quantity')
            ->selectRaw('SUM(order_details.line_total) as revenue')
            ->groupBy('order_details.name', 'order_details.sku')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'name' => (string) $row->name,
                'sku' => (string) $row->sku,
                'quantity' => (int) $row->quantity,
                'revenue' => (float) $row->revenue,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function customerSpend(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return $this->ordersBetween($start, $end, $filters)
            ->whereIn('payment_status', [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value, PaymentStatus::Refunded->value])
            ->selectRaw('customer_name, customer_email')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(grand_total) as spend')
            ->groupBy('customer_name', 'customer_email')
            ->orderByDesc('spend')
            ->limit(8)
            ->get()
            ->map(fn (object $row): array => [
                'customer_name' => (string) $row->customer_name,
                'customer_email' => (string) $row->customer_email,
                'orders' => (int) $row->orders,
                'spend' => (float) $row->spend,
            ]);
    }

    /**
     * @return Collection<int, array<string, string|float>>
     */
    private function purchaseOrders(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return PurchaseOrder::query()
            ->with('supplier:id,name')
            ->whereBetween(DB::raw('DATE(COALESCE(ordered_at, created_at))'), [$start->toDateString(), $end->toDateString()])
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (PurchaseOrder $order): array => [
                'po_number' => $order->po_number,
                'supplier' => $order->supplier?->name ?? 'Unknown supplier',
                'status' => $order->statusLabel(),
                'ordered_at' => $order->ordered_at?->format('Y-m-d') ?? $order->created_at->format('Y-m-d'),
                'subtotal' => (float) $order->subtotal,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int>>
     */
    private function paymentMix(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return $this->ordersBetween($start, $end, $filters)
            ->selectRaw('payment_status, COUNT(*) as count')
            ->groupBy('payment_status')
            ->orderByDesc('count')
            ->get()
            ->map(function (object $row): array {
                $status = $row->payment_status instanceof PaymentStatus
                    ? $row->payment_status->value
                    : (string) $row->payment_status;

                return [
                    'payment_status' => PaymentStatus::tryFrom($status)?->label() ?? ucfirst($status),
                    'count' => (int) $row->count,
                ];
            });
    }
}
