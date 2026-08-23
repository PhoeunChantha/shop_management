<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\ReturnRequest;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Classic sales report: a summary of what was sold in the period, a day-by-day
 * breakdown with totals, and the order-level detail behind both. Profit / COGS,
 * category mix and payment-method mix intentionally live in the Finance,
 * Products and Payments reports — this page is about sales only.
 */
final class SalesReportService extends ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);

        $daily = $this->salesByDay($start, $end, $filters);

        return [
            'filters' => $this->appliedFilters($start, $end, $filters),
            'summary' => $this->summary($start, $end, $filters),
            'daily' => $daily,
            'dailyTotals' => $this->totalsFor($daily),
            'orders' => $this->orders($start, $end, $filters),
        ];
    }

    /**
     * Period totals. Money figures come from paid orders only (paid, partially
     * refunded, refunded) so unpaid / cancelled orders never inflate sales.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    private function summary(CarbonImmutable $start, CarbonImmutable $end, array $filters): array
    {
        $paid = $this->ordersBetween($start, $end, $filters)
            ->whereIn('payment_status', $this->paidStatuses());

        $row = (clone $paid)
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as gross_sales')
            ->selectRaw('COALESCE(SUM(discount_total), 0) as discounts')
            ->selectRaw('COALESCE(SUM(tax_total), 0) as tax')
            ->selectRaw('COALESCE(SUM(shipping_total), 0) as shipping')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total_sales')
            ->first();

        $items = (int) DB::table('order_details')
            ->whereIn('order_id', (clone $paid)->select('orders.id'))
            ->sum('quantity');

        $refunds = $this->refundsBetween($start, $end, $filters);

        $orders = (int) ($row->orders ?? 0);
        $gross = (float) ($row->gross_sales ?? 0);
        $discounts = (float) ($row->discounts ?? 0);
        $total = (float) ($row->total_sales ?? 0);

        return [
            'orders' => $orders,
            'all_orders' => $this->ordersBetween($start, $end, $filters)->count(),
            'items' => $items,
            'gross_sales' => $gross,
            'discounts' => $discounts,
            'refunds' => $refunds,
            'net_sales' => $gross - $discounts,
            'tax' => (float) ($row->tax ?? 0),
            'shipping' => (float) ($row->shipping ?? 0),
            'total_sales' => $total,
            'average_order' => $orders > 0 ? $total / $orders : 0.0,
        ];
    }

    /**
     * Refunded amount in range (customer-aware via the parent order).
     *
     * @param  array<string, mixed>  $filters
     */
    private function refundsBetween(CarbonImmutable $start, CarbonImmutable $end, array $filters): float
    {
        return (float) ReturnRequest::query()
            ->join('orders', 'orders.id', '=', 'return_requests.order_id')
            ->whereIn('return_requests.refund_status', ['partial', 'refunded'])
            ->whereBetween(DB::raw('DATE(COALESCE(return_requests.refunded_at, return_requests.updated_at))'), [$start->toDateString(), $end->toDateString()])
            ->when(filled($filters['customer'] ?? null), fn (Builder $q) => $q->where(function (Builder $inner) use ($filters) {
                $inner->where('orders.customer_email', $filters['customer'])->orWhere('orders.customer_name', $filters['customer']);
            }))
            ->sum('return_requests.refund_amount');
    }

    /**
     * One row per calendar day in range (gap-filled), paid orders only.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function salesByDay(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        $rows = $this->ordersBetween($start, $end, $filters)
            ->whereIn('payment_status', $this->paidStatuses())
            ->leftJoinSub(
                DB::table('order_details')->select('order_id')->selectRaw('SUM(quantity) as items')->groupBy('order_id'),
                'la',
                'la.order_id',
                '=',
                'orders.id',
            )
            ->selectRaw('DATE(COALESCE(orders.placed_at, orders.created_at)) as d')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(la.items), 0) as items')
            ->selectRaw('SUM(orders.subtotal) as gross_sales')
            ->selectRaw('SUM(orders.discount_total) as discounts')
            ->selectRaw('SUM(orders.tax_total) as tax')
            ->selectRaw('SUM(orders.shipping_total) as shipping')
            ->selectRaw('SUM(orders.grand_total) as total_sales')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $out = [];
        $cursor = $start->startOfDay();
        $last = $end->startOfDay();

        // Cap at ~13 months of daily rows so a runaway custom range stays sane.
        while ($cursor->lessThanOrEqualTo($last) && count($out) <= 400) {
            $key = $cursor->toDateString();
            $row = $rows->get($key);
            $gross = (float) ($row->gross_sales ?? 0);
            $discounts = (float) ($row->discounts ?? 0);

            $out[] = [
                'date' => $key,
                'label' => $cursor->format('D, M d'),
                'orders' => (int) ($row->orders ?? 0),
                'items' => (int) ($row->items ?? 0),
                'gross_sales' => $gross,
                'discounts' => $discounts,
                'net_sales' => $gross - $discounts,
                'tax' => (float) ($row->tax ?? 0),
                'shipping' => (float) ($row->shipping ?? 0),
                'total_sales' => (float) ($row->total_sales ?? 0),
            ];

            $cursor = $cursor->addDay();
        }

        return collect($out);
    }

    /**
     * Footer totals for the day table.
     *
     * @param  Collection<int, array<string, string|int|float>>  $daily
     * @return array<string, float|int>
     */
    private function totalsFor(Collection $daily): array
    {
        $sum = fn (string $key) => $daily->sum(fn (array $r) => $r[$key]);

        return [
            'orders' => (int) $sum('orders'),
            'items' => (int) $sum('items'),
            'gross_sales' => (float) $sum('gross_sales'),
            'discounts' => (float) $sum('discounts'),
            'net_sales' => (float) $sum('net_sales'),
            'tax' => (float) $sum('tax'),
            'shipping' => (float) $sum('shipping'),
            'total_sales' => (float) $sum('total_sales'),
        ];
    }

    /**
     * Order-level detail: DB-paginated, searchable, sortable. Lists every order
     * matching the filters (including unpaid) so the admin can reconcile the
     * summary against what was actually placed.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function orders(CarbonImmutable $start, CarbonImmutable $end, array $filters): LengthAwarePaginator
    {
        $lineAgg = DB::table('order_details')
            ->select('order_id')
            ->selectRaw('SUM(quantity) as items')
            ->groupBy('order_id');

        $sortMap = [
            'date' => DB::raw('COALESCE(orders.placed_at, orders.created_at)'),
            'net' => DB::raw('(orders.subtotal - orders.discount_total)'),
            'total' => 'orders.grand_total',
        ];
        $sort = (string) ($filters['sort'] ?? 'date');
        $column = $sortMap[$sort] ?? $sortMap['date'];
        $direction = mb_strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) ($filters['search'] ?? ''));

        $query = $this->ordersBetween($start, $end, $filters)
            ->leftJoinSub($lineAgg, 'la', 'la.order_id', '=', 'orders.id')
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $inner) use ($search) {
                $inner->where('orders.order_number', 'like', "%{$search}%")
                    ->orWhere('orders.customer_name', 'like', "%{$search}%")
                    ->orWhere('orders.customer_email', 'like', "%{$search}%");
            }))
            ->select('orders.*')
            ->selectRaw('COALESCE(la.items, 0) as items_count')
            ->orderBy($column, $direction);

        return $query->paginate($this->perPage($filters, 25))->withQueryString()->through(function (Order $order): array {
            $gross = (float) $order->subtotal;
            $discount = (float) $order->discount_total;

            return [
                'date' => optional($order->placed_at ?? $order->created_at)->format('M d, Y'),
                'time' => optional($order->placed_at ?? $order->created_at)->format('H:i'),
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
                'net_sales' => $gross - $discount,
                'tax' => (float) $order->tax_total,
                'shipping' => (float) $order->shipping_total,
                'total' => (float) $order->grand_total,
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

        // Reuse the orders query but export the whole result set (no paging).
        $filters['per_page'] = 100000;
        $rows = $this->orders($start, $end, $filters)->items();

        $out = [[
            'Date', 'Order', 'Customer', 'Email', 'Phone', 'Status', 'Payment', 'Items',
            'Gross Sales', 'Discount', 'Net Sales', 'Tax', 'Shipping', 'Total',
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
                $row['items'],
                number_format($row['gross'], 2, '.', ''),
                number_format($row['discount'], 2, '.', ''),
                number_format($row['net_sales'], 2, '.', ''),
                number_format($row['tax'], 2, '.', ''),
                number_format($row['shipping'], 2, '.', ''),
                number_format($row['total'], 2, '.', ''),
            ];
        }

        return $out;
    }
}
