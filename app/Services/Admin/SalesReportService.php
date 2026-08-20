<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\OrderDetail;
use App\Models\ReturnRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SalesReportService extends ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);
        $orders = $this->ordersBetween($start, $end, $filters);
        $paidOrders = (clone $orders)->whereIn('payment_status', $this->paidStatuses());

        $merchandise = (float) (clone $paidOrders)->sum('subtotal');
        $discounts = (float) (clone $paidOrders)->sum('discount_total');
        $totalRevenue = (float) (clone $paidOrders)->sum('grand_total');
        $paidCount = (clone $paidOrders)->count();
        $netSales = $merchandise - $discounts;

        $cogs = (float) OrderDetail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereBetween(DB::raw('DATE(COALESCE(orders.placed_at, orders.created_at))'), [$start->toDateString(), $end->toDateString()])
            ->whereIn('orders.payment_status', $this->paidStatuses())
            ->when(filled($filters['status'] ?? null), fn (Builder $q) => $q->where('orders.status', $filters['status']))
            ->when(filled($filters['payment_status'] ?? null), fn (Builder $q) => $q->where('orders.payment_status', $filters['payment_status']))
            ->sum(DB::raw('COALESCE(order_details.unit_cost, 0) * order_details.quantity'));

        $refunds = (float) ReturnRequest::query()
            ->whereIn('refund_status', ['partial', 'refunded'])
            ->whereBetween(DB::raw('DATE(COALESCE(refunded_at, updated_at))'), [$start->toDateString(), $end->toDateString()])
            ->sum('refund_amount');

        return [
            'filters' => $this->appliedFilters($start, $end, $filters),
            'summary' => [
                'gross_sales' => $merchandise,
                'discount_total' => $discounts,
                'net_sales' => $netSales,
                'tax_total' => (float) (clone $paidOrders)->sum('tax_total'),
                'shipping_total' => (float) (clone $paidOrders)->sum('shipping_total'),
                'total_revenue' => $totalRevenue,
                'refunds' => $refunds,
                'net_revenue' => $totalRevenue - $refunds,
                'cogs' => $cogs,
                'gross_profit' => $netSales - $cogs,
                'margin' => $netSales > 0 ? round((($netSales - $cogs) / $netSales) * 100, 1) : 0.0,
                'orders' => (clone $orders)->count(),
                'paid_orders' => $paidCount,
                'average_order' => $paidCount > 0 ? $totalRevenue / $paidCount : 0.0,
            ],
            'salesByDay' => $this->paginateRows($this->salesByDay($start, $end, $filters), $filters, ['date']),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);

        return $this->salesByDay($start, $end, $filters)
            ->prepend(['Date' => 'Date', 'Orders' => 'Orders', 'Gross Sales' => 'Gross Sales', 'Tax' => 'Tax', 'Shipping' => 'Shipping', 'Discounts' => 'Discounts'])
            ->map(fn ($row) => array_values($row))
            ->all();
    }

    /**
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
}
