<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Services\Admin\Reports\Comparison;
use App\Services\Admin\Reports\PaymentMethodNames;
use App\Services\Admin\Reports\ReportFilters;
use App\Services\Admin\Reports\SalesLedger;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Sales domain: the waterfall for the period and the breakdowns behind it
 * (by date, product, category, customer, payment method, and the sale
 * transactions). Every money figure comes from {@see SalesLedger}; this
 * service only picks the grouping, sorting and paging.
 */
final class SalesReportService
{
    public const VIEWS = ['summary', 'products', 'categories', 'customers', 'methods', 'orders'];

    /** Sortable columns per table → SQL alias. */
    private const SORTS = [
        'products' => ['name' => 'name', 'quantity' => 'quantity', 'orders' => 'orders', 'gross' => 'gross_sales', 'refunds' => 'refunds', 'net' => 'net_sales', 'profit' => 'profit'],
        'categories' => ['quantity' => 'quantity', 'orders' => 'orders', 'gross' => 'gross_sales', 'refunds' => 'refunds', 'net' => 'net_sales', 'profit' => 'profit'],
        'customers' => ['name' => 'customer_name', 'orders' => 'orders', 'gross' => 'gross_sales', 'refunds' => 'refunds', 'net' => 'net_sales', 'total' => 'total_sales', 'last' => 'last_order_at'],
        'orders' => ['date' => 'placed_at', 'net' => 'net_sales', 'total' => 'total_sales', 'refunds' => 'total_refund'],
    ];

    public function __construct(
        private readonly SalesLedger $ledger,
        private readonly PaymentMethodNames $methods,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(ReportFilters $filters, string $view, bool $withFinance): array
    {
        $view = in_array($view, self::VIEWS, true) ? $view : 'summary';
        $previous = $filters->previous();

        $summary = $this->ledger->summary($filters) + ['units' => $this->ledger->units($filters)];
        $prior = $this->ledger->summary($previous) + ['units' => $this->ledger->units($previous)];

        return [
            'filters' => $filters,
            'view' => $view,
            'summary' => $summary,
            'comparison' => Comparison::many($summary, $prior, [
                'gross_sales', 'discounts', 'refunds', 'net_sales', 'total_sales', 'orders', 'units', 'average_order_value',
            ]),
            'unpaidOrders' => $this->unpaidCount($filters),
            ...match ($view) {
                'summary' => ['series' => $this->ledger->series($filters)],
                'products' => ['rows' => $this->products($filters, $withFinance)],
                'categories' => ['rows' => $this->categories($filters, $withFinance)],
                'customers' => ['rows' => $this->customers($filters)],
                'methods' => ['rows' => $this->paymentMethods($filters)],
                'orders' => ['rows' => $this->orders($filters)],
            },
        ];
    }

    /** Orders in the window that are not sales because payment was never captured. */
    private function unpaidCount(ReportFilters $filters): int
    {
        return $this->ledger->applyOrderFilters(
            DB::table('orders')
                ->whereNotIn('payment_status', SalesLedger::capturedStatuses())
                ->whereBetween('placed_at', [$filters->start, $filters->end]),
            $filters,
        )->count();
    }

    private function products(ReportFilters $filters, bool $withFinance, bool $paginate = true): LengthAwarePaginator|array
    {
        $query = $this->ledger->groupedLines($filters, 'x.product_id')
            ->selectRaw('SUM(x.line_total) - SUM(x.discount_alloc) - SUM(x.refund_alloc) - COALESCE(SUM(x.cost), 0) as profit')
            ->when($filters->search() !== '', fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                ->where('x.name', 'like', '%'.$filters->search().'%')
                ->orWhere('x.sku', 'like', '%'.$filters->search().'%')));

        $this->sort($query, 'products', $filters, $withFinance);

        $map = fn (object $row) => $this->lineRow($row, $withFinance) + [
            'name' => $row->group_key === null ? __('Deleted products') : (string) $row->name,
            'sku' => (string) ($row->sku ?? ''),
        ];

        return $paginate
            ? $query->paginate($filters->perPage())->withQueryString()->through($map)
            : $query->get()->map($map)->all();
    }

    private function categories(ReportFilters $filters, bool $withFinance, bool $paginate = true): LengthAwarePaginator|array
    {
        $query = $this->ledger
            ->groupedLines($filters, 'p.category_id', fn (Builder $q) => $q->leftJoin('products as p', 'p.id', '=', 'x.product_id'))
            ->selectRaw('SUM(x.line_total) - SUM(x.discount_alloc) - SUM(x.refund_alloc) - COALESCE(SUM(x.cost), 0) as profit');

        $this->sort($query, 'categories', $filters, $withFinance);

        $result = $paginate ? $query->paginate($filters->perPage())->withQueryString() : $query->get();
        $items = $paginate ? collect($result->items()) : $result;
        $names = Category::query()->whereIn('id', $items->pluck('group_key')->filter())->get()->pluck('name', 'id');

        $map = fn (object $row) => $this->lineRow($row, $withFinance) + [
            'name' => $row->group_key === null ? __('Uncategorized') : (string) ($names[$row->group_key] ?? __('Deleted category')),
        ];

        return $paginate ? $result->through($map) : $result->map($map)->all();
    }

    private function customers(ReportFilters $filters, bool $paginate = true): LengthAwarePaginator|array
    {
        $search = $filters->search();
        $query = $this->ledger->groupedOrders($filters, 'customer_email')
            ->selectRaw('MAX(customer_name) as customer_name')
            ->selectRaw('MAX(user_id) as user_id')
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                ->where('l.customer_email', 'like', "%{$search}%")
                ->orWhere('l.customer_name', 'like', "%{$search}%")));

        $this->sort($query, 'customers', $filters, false, 'net_sales');

        $map = fn (object $row) => $this->orderGroupRow($row) + [
            'customer_name' => (string) ($row->customer_name ?: __('Guest customer')),
            'customer_email' => (string) $row->group_key,
            'is_guest' => $row->user_id === null,
            'last_order_at' => $row->last_order_at ? CarbonImmutable::parse($row->last_order_at)->format('M d, Y') : '—',
        ];

        return $paginate
            ? $query->paginate($filters->perPage())->withQueryString()->through($map)
            : $query->get()->map($map)->all();
    }

    /**
     * Few rows (one per configured method) — not paginated.
     *
     * @return array<int, array<string, mixed>>
     */
    private function paymentMethods(ReportFilters $filters): array
    {
        return $this->ledger->groupedOrders($filters, 'payment_method')
            ->orderByDesc('total_sales')
            ->get()
            ->map(fn (object $row) => $this->orderGroupRow($row) + [
                'method' => $this->methods->for($row->group_key),
                'code' => (string) ($row->group_key ?? ''),
            ])
            ->all();
    }

    /**
     * The sale transactions behind the totals, with each order's refund
     * breakdown. Unpaid orders are not sales; they live in Orders & Fulfillment.
     */
    private function orders(ReportFilters $filters, bool $paginate = true): LengthAwarePaginator|array
    {
        $search = $filters->search();
        $items = DB::table('order_details')->select('order_id')->selectRaw('SUM(quantity) as units')->groupBy('order_id');

        $query = DB::query()
            ->fromSub($this->ledger->orders($filters), 'l')
            ->leftJoinSub($items, 'u', 'u.order_id', '=', 'l.id')
            ->select('l.*')
            ->selectRaw('COALESCE(u.units, 0) as units')
            ->selectRaw('l.subtotal - l.discount_total - l.merchandise_refund as net_sales')
            ->selectRaw('l.grand_total - l.total_refund as total_sales')
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                ->where('l.order_number', 'like', "%{$search}%")
                ->orWhere('l.customer_name', 'like', "%{$search}%")
                ->orWhere('l.customer_email', 'like', "%{$search}%")));

        $this->sort($query, 'orders', $filters, false, 'placed_at');

        $map = fn (object $row) => [
            'order_id' => (int) $row->id,
            'order_number' => (string) $row->order_number,
            'date' => CarbonImmutable::parse($row->placed_at)->format('M d, Y'),
            'time' => CarbonImmutable::parse($row->placed_at)->format('H:i'),
            'customer_name' => (string) ($row->customer_name ?: __('Guest customer')),
            'customer_email' => (string) $row->customer_email,
            'is_guest' => $row->user_id === null,
            'status' => OrderStatus::tryFrom((string) $row->status),
            'payment_status' => PaymentStatus::tryFrom((string) $row->payment_status),
            'method' => $this->methods->for($row->payment_method),
            'units' => (int) $row->units,
            'gross' => (float) $row->subtotal,
            'discount' => (float) $row->discount_total,
            'recorded_refund' => round((float) $row->recorded_refund, 2),
            'implied_refund' => round((float) $row->implied_refund, 2),
            'refund' => round((float) $row->total_refund, 2),
            'net_sales' => round((float) $row->net_sales, 2),
            'total' => round((float) $row->total_sales, 2),
        ];

        return $paginate
            ? $query->paginate($filters->perPage())->withQueryString()->through($map)
            : $query->get()->map($map)->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function lineRow(object $row, bool $withFinance): array
    {
        $net = round((float) $row->net_sales, 2);
        $out = [
            'quantity' => (int) $row->quantity,
            'orders' => (int) $row->orders,
            'gross' => round((float) $row->gross_sales, 2),
            'discounts' => round((float) $row->discounts, 2),
            'refunds' => round((float) $row->refunds, 2),
            'net_sales' => $net,
        ];

        if ($withFinance) {
            $cogs = round((float) $row->cogs, 2);
            $out += [
                'cogs' => $cogs,
                'profit' => round($net - $cogs, 2),
                'margin' => Comparison::percent($net - $cogs, $net),
                'uncosted_units' => (int) $row->uncosted_units,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function orderGroupRow(object $row): array
    {
        $orders = (int) $row->orders;

        return [
            'orders' => $orders,
            'gross' => round((float) $row->gross_sales, 2),
            'discounts' => round((float) $row->discounts, 2),
            'refunds' => round((float) $row->refunds, 2),
            'net_sales' => round((float) $row->net_sales, 2),
            'total_sales' => round((float) $row->total_sales, 2),
            'average_order_value' => $orders > 0 ? round(((float) $row->gross_sales - (float) $row->discounts) / $orders, 2) : 0.0,
        ];
    }

    /**
     * Whitelisted ORDER BY. Profit sorting is only honoured with finance access.
     */
    private function sort(Builder $query, string $table, ReportFilters $filters, bool $withFinance, string $default = 'net_sales'): void
    {
        $map = self::SORTS[$table];
        if (! $withFinance) {
            unset($map['profit']);
        }

        $column = $map[(string) $filters->get('sort')] ?? $default;
        $direction = $filters->get('direction') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction)->orderBy($table === 'orders' ? 'l.id' : 'group_key');
    }

    /**
     * Server-side customer autocomplete for the filter (never loads the full
     * customer base into the browser).
     *
     * @return array<int, array{value: string, name: string, email: string}>
     */
    public function customerOptions(string $term, int $limit = 20): array
    {
        return Order::query()
            ->whereNotNull('customer_email')
            ->where('customer_email', '!=', '')
            ->when($term !== '', fn (EloquentBuilder $q) => $q->where(function (EloquentBuilder $inner) use ($term) {
                $inner->where('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%");
            }))
            ->selectRaw('MAX(customer_name) as customer_name, customer_email')
            ->selectRaw('COUNT(*) as orders')
            ->groupBy('customer_email')
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
     * Export for the active tab, with every active filter (incl. search) applied.
     *
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(ReportFilters $filters, string $view, bool $withFinance): array
    {
        $money = fn ($v): string => number_format((float) $v, 2, '.', '');
        $finHead = $withFinance ? ['COGS', 'Gross Profit', 'Margin %'] : [];
        $fin = fn (array $r): array => $withFinance ? [$money($r['cogs']), $money($r['profit']), $r['margin'] ?? ''] : [];

        return match ($view) {
            'products' => [
                ['Product', 'SKU', 'Orders', 'Units', 'Gross Sales', 'Discounts', 'Product Refunds', 'Net Sales', ...$finHead],
                ...array_map(fn (array $r) => [$r['name'], $r['sku'], $r['orders'], $r['quantity'], $money($r['gross']), $money($r['discounts']), $money($r['refunds']), $money($r['net_sales']), ...$fin($r)], $this->products($filters, $withFinance, false)),
            ],
            'categories' => [
                ['Category', 'Orders', 'Units', 'Gross Sales', 'Discounts', 'Product Refunds', 'Net Sales', ...$finHead],
                ...array_map(fn (array $r) => [$r['name'], $r['orders'], $r['quantity'], $money($r['gross']), $money($r['discounts']), $money($r['refunds']), $money($r['net_sales']), ...$fin($r)], $this->categories($filters, $withFinance, false)),
            ],
            'customers' => [
                ['Customer', 'Email', 'Orders', 'Gross Sales', 'Discounts', 'Refunds', 'Net Sales', 'Total Sales', 'Last Order'],
                ...array_map(fn (array $r) => [$r['customer_name'], $r['customer_email'], $r['orders'], $money($r['gross']), $money($r['discounts']), $money($r['refunds']), $money($r['net_sales']), $money($r['total_sales']), $r['last_order_at']], $this->customers($filters, false)),
            ],
            'methods' => [
                ['Payment Method', 'Code', 'Orders', 'Gross Sales', 'Discounts', 'Refunds', 'Net Sales', 'Total Sales', 'Average Order'],
                ...array_map(fn (array $r) => [$r['method'], $r['code'], $r['orders'], $money($r['gross']), $money($r['discounts']), $money($r['refunds']), $money($r['net_sales']), $money($r['total_sales']), $money($r['average_order_value'])], $this->paymentMethods($filters)),
            ],
            'orders' => [
                ['Date', 'Order', 'Customer', 'Email', 'Status', 'Payment', 'Method', 'Units', 'Gross Sales', 'Discount', 'Recorded Refund', 'Implied Refund', 'Net Sales', 'Total Sales'],
                ...array_map(fn (array $r) => [$r['date'], $r['order_number'], $r['customer_name'], $r['customer_email'], $r['status']?->label() ?? '', $r['payment_status']?->label() ?? '', $r['method'], $r['units'], $money($r['gross']), $money($r['discount']), $money($r['recorded_refund']), $money($r['implied_refund']), $money($r['net_sales']), $money($r['total'])], $this->orders($filters, false)),
            ],
            default => SalesLedger::exportWaterfall($this->ledger->series($filters), 'Period'),
        };
    }
}
