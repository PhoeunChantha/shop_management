<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Services\Admin\Reports\Comparison;
use App\Services\Admin\Reports\PaymentMethodNames;
use App\Services\Admin\Reports\ReportFilters;
use App\Services\Admin\Reports\ReportSeries;
use App\Services\Admin\Reports\SalesLedger;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Executive summary: "how is the business performing?". Every sales and
 * refund figure comes from {@see SalesLedger}; this service only composes.
 * Cost / profit are computed only when the caller is allowed to see them.
 */
final class OverviewReportService
{
    public function __construct(
        private readonly SalesLedger $ledger,
        private readonly PaymentMethodNames $methods,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(ReportFilters $filters, bool $withFinance): array
    {
        $previous = $filters->previous();
        $unit = ReportSeries::unit($filters);

        $summary = $this->ledger->summary($filters) + ['units' => $this->ledger->units($filters)];
        $prior = $this->ledger->summary($previous) + ['units' => $this->ledger->units($previous)];

        $customers = $this->customerMix($filters);
        $priorCustomers = $this->customerMix($previous);
        $summary['new_customers'] = $customers['new']['customers'];
        $prior['new_customers'] = $priorCustomers['new']['customers'];

        $finance = null;
        if ($withFinance) {
            $finance = $this->finance($filters, $summary);
            $priorFinance = $this->finance($previous, $prior);
            $summary['gross_profit'] = $finance['gross_profit'];
            $prior['gross_profit'] = $priorFinance['gross_profit'];
        }

        $keys = ['total_sales', 'net_sales', 'orders', 'average_order_value', 'units', 'refunds', 'new_customers'];
        if ($withFinance) {
            $keys[] = 'gross_profit';
        }

        return [
            'filters' => $filters,
            'summary' => $summary,
            'comparison' => Comparison::many($summary, $prior, $keys),
            'finance' => $finance,
            'awaiting' => $this->awaitingPayment($filters),
            'chart' => $this->chart($filters, $previous, $unit),
            'unit' => $unit,
            'topProducts' => $this->topProducts($filters),
            'topCategories' => $this->topCategories($filters),
            'paymentMix' => $this->paymentMix($filters),
            'customerMix' => $customers,
        ];
    }

    /**
     * Gross profit from the line cost snapshot. Lines with no cost are
     * surfaced (uncosted_units) so a margin is never silently overstated.
     *
     * @param  array<string, mixed>  $summary
     * @return array{cogs: float, gross_profit: float, margin: float|null, uncosted_units: int, units: int}
     */
    private function finance(ReportFilters $filters, array $summary): array
    {
        $cost = $this->ledger->cost($filters);
        $profit = round($summary['net_sales'] - $cost['cogs'], 2);

        return [
            ...$cost,
            'gross_profit' => $profit,
            'margin' => Comparison::percent($profit, $summary['net_sales']),
        ];
    }

    /**
     * Total sales per bucket for the window and, aligned by position, for the
     * previous window (ghost line).
     *
     * @return array<string, array<int, mixed>>
     */
    private function chart(ReportFilters $filters, ReportFilters $previous, string $unit): array
    {
        $current = $this->ledger->series($filters, $unit);
        $prior = $this->ledger->series($previous, $unit);

        return [
            'labels' => array_column($current, 'label'),
            'total_sales' => array_column($current, 'total_sales'),
            'orders' => array_column($current, 'orders'),
            'previous_total_sales' => array_map(fn ($i) => $prior[$i]['total_sales'] ?? 0, array_keys($current)),
        ];
    }

    /**
     * Unpaid, not-cancelled orders placed in the window — revenue that is not
     * a sale yet.
     *
     * @return array{orders: int, amount: float}
     */
    private function awaitingPayment(ReportFilters $filters): array
    {
        $row = DB::table('orders')
            ->where('payment_status', PaymentStatus::Unpaid->value)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->whereBetween('placed_at', [$filters->start, $filters->end])
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(grand_total), 0) as amount')
            ->first();

        return ['orders' => (int) $row->orders, 'amount' => round((float) $row->amount, 2)];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topProducts(ReportFilters $filters, int $limit = 5): array
    {
        return $this->ledger->groupedLines($filters, 'x.product_id')
            ->orderByDesc('net_sales')
            ->limit($limit)
            ->get()
            ->map(fn (object $row) => [
                'name' => $row->group_key === null ? __('Deleted products') : (string) $row->name,
                'sku' => (string) ($row->sku ?? ''),
                'quantity' => (int) $row->quantity,
                'net_sales' => round((float) $row->net_sales, 2),
            ])
            ->all();
    }

    /**
     * Net sales by root category (products.category_id).
     *
     * @return array<int, array<string, mixed>>
     */
    private function topCategories(ReportFilters $filters, int $limit = 5): array
    {
        $rows = $this->ledger
            ->groupedLines($filters, 'p.category_id', fn (Builder $q) => $q->leftJoin('products as p', 'p.id', '=', 'x.product_id'))
            ->orderByDesc('net_sales')
            ->limit($limit)
            ->get();

        $names = Category::query()->whereIn('id', $rows->pluck('group_key')->filter())->get()->pluck('name', 'id');

        return $rows->map(fn (object $row) => [
            'name' => $row->group_key === null ? __('Uncategorized') : (string) ($names[$row->group_key] ?? __('Deleted category')),
            'quantity' => (int) $row->quantity,
            'net_sales' => round((float) $row->net_sales, 2),
        ])->all();
    }

    /**
     * Total sales by the payment method recorded on the order.
     *
     * @return array<int, array<string, mixed>>
     */
    private function paymentMix(ReportFilters $filters): array
    {
        return $this->ledger->groupedOrders($filters, 'payment_method')
            ->orderByDesc('total_sales')
            ->get()
            ->map(fn (object $row) => [
                'method' => $this->methods->for($row->group_key),
                'orders' => (int) $row->orders,
                'total_sales' => round((float) $row->total_sales, 2),
            ])
            ->all();
    }

    /**
     * Buyers in the window split by whether they had a captured order before
     * the window starts. Keyed by customer_email so guest checkouts count too.
     *
     * @return array{new: array{customers: int, orders: int, total_sales: float}, returning: array{customers: int, orders: int, total_sales: float}}
     */
    private function customerMix(ReportFilters $filters): array
    {
        $prior = DB::table('orders')
            ->select('customer_email')
            ->whereIn('payment_status', SalesLedger::capturedStatuses())
            ->where('placed_at', '<', $filters->start)
            ->groupBy('customer_email');

        $rows = DB::query()
            ->fromSub($this->ledger->orders($filters), 'l')
            ->leftJoinSub($prior, 'prior', 'prior.customer_email', '=', 'l.customer_email')
            ->selectRaw("CASE WHEN prior.customer_email IS NULL THEN 'new' ELSE 'returning' END as segment")
            ->selectRaw('COUNT(DISTINCT l.customer_email) as customers')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(l.grand_total) - SUM(l.total_refund) as total_sales')
            ->groupBy('segment')
            ->get()
            ->keyBy('segment');

        $pick = fn (string $segment) => [
            'customers' => (int) ($rows[$segment]->customers ?? 0),
            'orders' => (int) ($rows[$segment]->orders ?? 0),
            'total_sales' => round((float) ($rows[$segment]->total_sales ?? 0), 2),
        ];

        return ['new' => $pick('new'), 'returning' => $pick('returning')];
    }

    /**
     * Sales by period, for the CSV/PDF export.
     *
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(ReportFilters $filters): array
    {
        return SalesLedger::exportWaterfall($this->ledger->series($filters), 'Period');
    }
}
