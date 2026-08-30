<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Category;
use App\Models\OrderDetail;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ProductReportService extends ReportService
{
    /**
     * Columns the table can be sorted on, mapped to their SQL expression.
     *
     * @var array<string, string>
     */
    private const SORTABLE = [
        'name' => 'order_details.name',
        'sku' => 'sku',
        'quantity' => 'quantity',
        'revenue' => 'revenue',
        'cogs' => 'cogs',
        'profit' => 'profit',
        'margin' => 'margin',
    ];

    /**
     * How many of the top/bottom performers (by revenue) are flagged for the
     * "Best seller" / "Slow mover" badges. Only applied once the filtered set
     * is large enough that the two groups can't overlap.
     */
    private const BADGE_COUNT = 3;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);
        [$prevStart, $prevEnd] = $this->previousRange($start, $end);

        // The whole period, unaffected by table search — mirrors how the KPI
        // tiles elsewhere in the Reports module stay stable while a table search
        // narrows only the table underneath them.
        $products = $this->topProducts($start, $end, $filters);
        $previousProducts = $this->topProducts($prevStart, $prevEnd, $filters);

        $summary = $this->aggregate($products);
        $previousSummary = $this->aggregate($previousProducts);

        $filtered = $this->filterRows($products, $filters, ['name', 'sku']);

        return [
            'filters' => $this->appliedFilters($start, $end, $filters) + [
                'category_id' => $filters['category_id'] ?? null,
                'sort' => $filters['sort'] ?? null,
                'direction' => $filters['direction'] ?? null,
            ],
            'summary' => $summary,
            'comparison' => $this->comparison($summary, $previousSummary, ['units', 'revenue', 'cogs', 'profit', 'margin']),
            'tableTotals' => $this->aggregate($filtered),
            'products' => $this->paginate($filtered, $filters),
            'badges' => $this->badges($products),
            'chart' => $this->chartRows($products),
            'categories' => Category::treeOptions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);

        $columns = ['name', 'sku', 'quantity', 'revenue', 'cogs', 'profit', 'margin', 'stock'];

        return $this->filterRows($this->topProducts($start, $end, $filters), $filters, ['name', 'sku'])
            ->prepend(['name' => 'Product', 'sku' => 'SKU', 'quantity' => 'Quantity', 'revenue' => 'Revenue', 'cogs' => 'COGS', 'profit' => 'Profit', 'margin' => 'Margin %', 'stock' => 'Stock'])
            ->map(fn ($row) => array_values(array_intersect_key($row, array_flip($columns))))
            ->all();
    }

    /**
     * Aggregate totals across an already-computed product collection — reused
     * for the whole-period summary tiles, the previous-period comparison, and
     * the search-filtered table totals footer.
     *
     * @param  Collection<int, array<string, mixed>>  $products
     * @return array<string, int|float>
     */
    private function aggregate(Collection $products): array
    {
        $revenue = (float) $products->sum('revenue');
        $cogs = (float) $products->sum('cogs');
        $profit = $revenue - $cogs;

        return [
            'products' => $products->count(),
            'units' => (int) $products->sum('quantity'),
            'revenue' => $revenue,
            'cogs' => $cogs,
            'profit' => $profit,
            'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0.0,
        ];
    }

    /**
     * Top 10 by revenue, for the ranking bar chart — always sorted by revenue
     * regardless of the table's own sort, since it answers a different question
     * ("what sells most") than the sortable table does.
     *
     * @param  Collection<int, array<string, mixed>>  $products
     * @return array<int, array{name: string, revenue: float}>
     */
    private function chartRows(Collection $products): array
    {
        return $products->sortByDesc('revenue')
            ->take(10)
            ->map(fn (array $row): array => ['name' => $row['name'], 'revenue' => $row['revenue']])
            ->values()
            ->all();
    }

    /**
     * "Best seller" for the top performers by revenue, "Slow mover" for the
     * bottom performers — only when the set is large enough that the two
     * groups can't overlap. Keyed by "name|sku" so the view can match a row.
     *
     * @param  Collection<int, array<string, mixed>>  $products
     * @return array<string, string>
     */
    private function badges(Collection $products): array
    {
        if ($products->count() < self::BADGE_COUNT * 2) {
            return [];
        }

        $ranked = $products->sortByDesc('revenue')->values();
        $key = fn (array $row): string => $row['name'].'|'.$row['sku'];

        $badges = [];
        foreach ($ranked->take(self::BADGE_COUNT) as $row) {
            $badges[$key($row)] = 'best-seller';
        }
        foreach ($ranked->slice(-self::BADGE_COUNT) as $row) {
            $badges[$key($row)] = 'slow-mover';
        }

        return $badges;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function topProducts(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        $sortKey = self::SORTABLE[$filters['sort'] ?? ''] ?? 'revenue';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return OrderDetail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_details.product_id')
            ->whereBetween(DB::raw('DATE(COALESCE(orders.placed_at, orders.created_at))'), [$start->toDateString(), $end->toDateString()])
            ->whereIn('orders.payment_status', $this->paidStatuses())
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('orders.status', $filters['status']))
            ->when(filled($filters['payment_status'] ?? null), fn ($query) => $query->where('orders.payment_status', $filters['payment_status']))
            ->when(filled($filters['category_id'] ?? null), fn ($query) => $query->where(function (Builder $q) use ($filters) {
                $q->where('products.category_id', $filters['category_id'])
                    ->orWhere('products.sub_category_id', $filters['category_id']);
            }))
            ->selectRaw('order_details.name, COALESCE(order_details.sku, "") as sku')
            ->selectRaw('MIN(order_details.product_id) as product_id')
            ->selectRaw('MAX(order_details.image) as image')
            ->selectRaw('MAX(products.stock) as stock')
            ->selectRaw('MAX(products.low_stock_alert) as low_stock_alert')
            ->selectRaw('MAX(products.product_type) as product_type')
            ->selectRaw('SUM(order_details.quantity) as quantity')
            ->selectRaw('SUM(order_details.line_total) as revenue')
            ->selectRaw('SUM(COALESCE(order_details.unit_cost, 0) * order_details.quantity) as cogs')
            ->selectRaw('(SUM(order_details.line_total) - SUM(COALESCE(order_details.unit_cost, 0) * order_details.quantity)) as profit')
            ->selectRaw('CASE WHEN SUM(order_details.line_total) > 0 THEN ROUND(((SUM(order_details.line_total) - SUM(COALESCE(order_details.unit_cost, 0) * order_details.quantity)) / SUM(order_details.line_total)) * 100, 1) ELSE 0 END as margin')
            ->groupBy('order_details.name', 'order_details.sku')
            ->orderBy($sortKey, $direction)
            ->limit(5000)
            ->get()
            ->map(function (object $row): array {
                $revenue = (float) $row->revenue;
                $cogs = (float) $row->cogs;

                return [
                    'product_id' => $row->product_id !== null ? (int) $row->product_id : null,
                    'image' => $row->image,
                    'name' => (string) $row->name,
                    'sku' => (string) $row->sku,
                    'quantity' => (int) $row->quantity,
                    'revenue' => $revenue,
                    'cogs' => $cogs,
                    'profit' => $revenue - $cogs,
                    'margin' => $revenue > 0 ? round((($revenue - $cogs) / $revenue) * 100, 1) : 0.0,
                    'stock' => $row->stock !== null ? (int) $row->stock : null,
                    'low_stock_alert' => $row->low_stock_alert !== null ? (int) $row->low_stock_alert : null,
                    'product_type' => $row->product_type,
                ];
            });
    }
}
