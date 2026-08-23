<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\OrderDetail;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ProductReportService extends ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);
        $products = $this->topProducts($start, $end, $filters);

        return [
            'filters' => $this->appliedFilters($start, $end, $filters),
            'summary' => [
                'products' => $products->count(),
                'units' => (int) $products->sum('quantity'),
                'revenue' => (float) $products->sum('revenue'),
                'cogs' => (float) $products->sum('cogs'),
                'profit' => (float) $products->sum('profit'),
                'margin' => $products->sum('revenue') > 0 ? round(($products->sum('profit') / $products->sum('revenue')) * 100, 1) : 0.0,
            ],
            'products' => $this->paginateRows($products, $filters, ['name', 'sku']),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);

        return $this->topProducts($start, $end, $filters)
            ->prepend(['Product' => 'Product', 'SKU' => 'SKU', 'Quantity' => 'Quantity', 'Revenue' => 'Revenue', 'COGS' => 'COGS', 'Profit' => 'Profit', 'Margin %' => 'Margin %'])
            ->map(fn ($row) => array_values($row))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function topProducts(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return OrderDetail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereBetween(DB::raw('DATE(COALESCE(orders.placed_at, orders.created_at))'), [$start->toDateString(), $end->toDateString()])
            ->whereIn('orders.payment_status', $this->paidStatuses())
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('orders.status', $filters['status']))
            ->when(filled($filters['payment_status'] ?? null), fn ($query) => $query->where('orders.payment_status', $filters['payment_status']))
            ->selectRaw('order_details.name, COALESCE(order_details.sku, "") as sku')
            ->selectRaw('SUM(order_details.quantity) as quantity')
            ->selectRaw('SUM(order_details.line_total) as revenue')
            ->selectRaw('SUM(COALESCE(order_details.unit_cost, 0) * order_details.quantity) as cogs')
            ->groupBy('order_details.name', 'order_details.sku')
            ->orderByDesc('revenue')
            ->limit(5000)
            ->get()
            ->map(function (object $row): array {
                $revenue = (float) $row->revenue;
                $cogs = (float) $row->cogs;

                return [
                    'name' => (string) $row->name,
                    'sku' => (string) $row->sku,
                    'quantity' => (int) $row->quantity,
                    'revenue' => $revenue,
                    'cogs' => $cogs,
                    'profit' => $revenue - $cogs,
                    'margin' => $revenue > 0 ? round((($revenue - $cogs) / $revenue) * 100, 1) : 0.0,
                ];
            });
    }
}
