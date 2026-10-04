<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Services\Admin\Reports\Comparison;
use App\Services\Admin\Reports\PaymentMethodNames;
use App\Services\Admin\Reports\ReportFilters;
use App\Services\Admin\Reports\SalesLedger;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Orders & Fulfillment: "what is happening to my orders?". Counts every order
 * placed in the window regardless of payment (this is operations, not
 * revenue — money figures live in Sales via SalesLedger).
 *
 * Statuses are exactly {@see OrderStatus} / {@see FulfillmentStatus}. Timing
 * uses the timestamps the app records (placed_at, shipped_at, fulfilled_at);
 * there is no delivered_at, so delivery time is not reported.
 */
final class OrderReportService
{
    public const VIEWS = ['summary', 'orders'];

    private const SORTS = ['date' => 'placed_at', 'total' => 'grand_total', 'status' => 'status', 'ship' => 'ship_seconds'];

    public function __construct(
        private readonly SalesLedger $ledger,
        private readonly PaymentMethodNames $methods,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(ReportFilters $filters, string $view): array
    {
        $view = in_array($view, self::VIEWS, true) ? $view : 'summary';

        $statuses = $this->statusBreakdown($filters);
        $prior = $this->statusBreakdown($filters->previous());
        $total = array_sum(array_column($statuses, 'count'));
        $priorTotal = array_sum(array_column($prior, 'count'));
        $count = fn (array $rows, OrderStatus $s) => $rows[$s->value]['count'] ?? 0;

        $kpis = [];
        foreach ([null, OrderStatus::Pending, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Cancelled] as $status) {
            $key = $status?->value ?? 'total';
            $kpis[$key] = [
                'label' => $status?->label() ?? __('Total orders'),
                'value' => $status ? $count($statuses, $status) : $total,
                'comparison' => Comparison::of($status ? $count($statuses, $status) : $total, $status ? $count($prior, $status) : $priorTotal),
                'status' => $status?->value,
            ];
        }

        return [
            'filters' => $filters,
            'view' => $view,
            'total' => $total,
            'kpis' => $kpis,
            'statuses' => array_values($statuses),
            ...($view === 'summary'
                ? [
                    'fulfillment' => $this->fulfillmentBreakdown($filters),
                    'cancellations' => $this->cancellations($filters),
                    'timing' => $this->timing($filters),
                    'backlog' => $this->backlog(),
                ]
                : ['rows' => $this->orders($filters)]),
        ];
    }

    /** All orders placed in the window with the report filters applied. */
    private function base(ReportFilters $filters): Builder
    {
        $search = $filters->search();

        return $this->ledger->applyOrderFilters(DB::table('orders'), $filters)
            ->whereBetween('orders.placed_at', [$filters->start, $filters->end])
            ->when($filters->get('fulfillment_status'), fn (Builder $q, $v) => $q->where('orders.fulfillment_status', $v))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                ->where('orders.order_number', 'like', "%{$search}%")
                ->orWhere('orders.customer_name', 'like', "%{$search}%")
                ->orWhere('orders.customer_email', 'like', "%{$search}%")));
    }

    /**
     * One row per OrderStatus case (zero-filled), keyed by value.
     *
     * @return array<string, array{status: string, label: string, color: string, count: int, value: float, share: float|null}>
     */
    private function statusBreakdown(ReportFilters $filters): array
    {
        $rows = $this->base($filters)
            ->selectRaw('status, COUNT(*) as c, COALESCE(SUM(grand_total), 0) as v')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        $total = (int) $rows->sum('c');

        $out = [];
        foreach (OrderStatus::cases() as $status) {
            $c = (int) ($rows[$status->value]->c ?? 0);
            $out[$status->value] = [
                'status' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => $c,
                'value' => round((float) ($rows[$status->value]->v ?? 0), 2),
                'share' => Comparison::percent($c, $total),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array{status: string, label: string, count: int, share: float|null}>
     */
    private function fulfillmentBreakdown(ReportFilters $filters): array
    {
        $rows = $this->base($filters)
            ->selectRaw('fulfillment_status, COUNT(*) as c')
            ->groupBy('fulfillment_status')
            ->pluck('c', 'fulfillment_status');
        $total = (int) $rows->sum();

        return array_map(fn (FulfillmentStatus $s) => [
            'status' => $s->value,
            'label' => $s->label(),
            'count' => (int) ($rows[$s->value] ?? 0),
            'share' => Comparison::percent((int) ($rows[$s->value] ?? 0), $total),
        ], FulfillmentStatus::cases());
    }

    /**
     * Cancelled orders split by whether payment had been captured. A captured
     * cancellation is money the store may still owe back — see Returns & Refunds.
     *
     * @return array{total: int, before_payment: int, after_payment: int, after_payment_value: float, rate: float|null}
     */
    private function cancellations(ReportFilters $filters): array
    {
        $captured = SalesLedger::capturedStatuses();
        $row = $this->base($filters)
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled', [OrderStatus::Cancelled->value])
            ->selectRaw(
                'SUM(CASE WHEN status = ? AND payment_status IN (?, ?, ?) THEN 1 ELSE 0 END) as after_payment',
                [OrderStatus::Cancelled->value, ...$captured],
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? AND payment_status IN (?, ?, ?) THEN grand_total ELSE 0 END) as after_value',
                [OrderStatus::Cancelled->value, ...$captured],
            )
            ->first();

        $cancelled = (int) $row->cancelled;
        $after = (int) $row->after_payment;

        return [
            'total' => $cancelled,
            'before_payment' => $cancelled - $after,
            'after_payment' => $after,
            'after_payment_value' => round((float) $row->after_value, 2),
            'rate' => Comparison::percent($cancelled, (int) $row->total_orders),
        ];
    }

    /**
     * Time from placement to shipment / fulfilment, for orders in the window
     * that have the timestamp. Negative spans (back-dated manual entry) are
     * excluded rather than averaged in.
     *
     * @return array<string, array{orders: int, avg_hours: float|null, within_48h: float|null}>
     */
    private function timing(ReportFilters $filters): array
    {
        $out = [];

        foreach (['ship' => 'shipped_at', 'fulfil' => 'fulfilled_at'] as $key => $column) {
            $span = $this->secondsBetween('placed_at', $column);
            $row = $this->base($filters)
                ->whereNotNull($column)
                ->whereRaw("{$span} >= 0")
                ->selectRaw('COUNT(*) as n')
                ->selectRaw("AVG({$span}) as avg_seconds")
                ->selectRaw("SUM(CASE WHEN {$span} <= 172800 THEN 1 ELSE 0 END) as fast")
                ->first();

            $n = (int) $row->n;
            $out[$key] = [
                'orders' => $n,
                'avg_hours' => $n > 0 ? round((float) $row->avg_seconds / 3600, 1) : null,
                'within_48h' => Comparison::percent((int) $row->fast, $n),
            ];
        }

        return $out;
    }

    /**
     * Open, not-yet-fulfilled orders right now (any placement date), aged
     * since placement — the queue the team has to work through today.
     *
     * @return array{total: int, buckets: array<int, array{label: string, count: int}>}
     */
    private function backlog(): array
    {
        $now = CarbonImmutable::now();
        $open = array_map(fn (OrderStatus $s) => $s->value, array_filter(OrderStatus::cases(), fn (OrderStatus $s) => $s->isOpen()));

        $row = DB::table('orders')
            ->whereIn('status', $open)
            ->where('fulfillment_status', '!=', FulfillmentStatus::Fulfilled->value)
            ->selectRaw('SUM(CASE WHEN placed_at >= ? THEN 1 ELSE 0 END) as d1', [$now->subDay()])
            ->selectRaw('SUM(CASE WHEN placed_at < ? AND placed_at >= ? THEN 1 ELSE 0 END) as d3', [$now->subDay(), $now->subDays(3)])
            ->selectRaw('SUM(CASE WHEN placed_at < ? AND placed_at >= ? THEN 1 ELSE 0 END) as d7', [$now->subDays(3), $now->subDays(7)])
            ->selectRaw('SUM(CASE WHEN placed_at < ? THEN 1 ELSE 0 END) as older', [$now->subDays(7)])
            ->first();

        $buckets = [
            ['label' => __('Under 24 hours'), 'count' => (int) $row->d1],
            ['label' => __('1–3 days'), 'count' => (int) $row->d3],
            ['label' => __('3–7 days'), 'count' => (int) $row->d7],
            ['label' => __('Over 7 days'), 'count' => (int) $row->older],
        ];

        return ['total' => array_sum(array_column($buckets, 'count')), 'buckets' => $buckets];
    }

    private function orders(ReportFilters $filters, bool $paginate = true): LengthAwarePaginator|array
    {
        $span = $this->secondsBetween('placed_at', 'shipped_at');
        $query = $this->base($filters)
            ->select('orders.id', 'orders.order_number', 'orders.placed_at', 'orders.customer_name', 'orders.customer_email',
                'orders.user_id', 'orders.status', 'orders.payment_status', 'orders.fulfillment_status', 'orders.payment_method',
                'orders.grand_total', 'orders.shipped_at', 'orders.fulfilled_at', 'orders.carrier')
            ->selectRaw("CASE WHEN orders.shipped_at IS NOT NULL AND {$span} >= 0 THEN {$span} END as ship_seconds");

        $column = self::SORTS[(string) $filters->get('sort')] ?? 'placed_at';
        $query->orderBy($column, $filters->get('direction') === 'asc' ? 'asc' : 'desc')->orderBy('orders.id', 'desc');

        $map = fn (object $row) => [
            'order_id' => (int) $row->id,
            'order_number' => (string) $row->order_number,
            'placed' => CarbonImmutable::parse($row->placed_at)->format('M d, Y H:i'),
            'customer_name' => (string) ($row->customer_name ?: __('Guest customer')),
            'customer_email' => (string) $row->customer_email,
            'status' => OrderStatus::tryFrom((string) $row->status),
            'payment_status' => PaymentStatus::tryFrom((string) $row->payment_status),
            'fulfillment_status' => FulfillmentStatus::tryFrom((string) $row->fulfillment_status),
            'method' => $this->methods->for($row->payment_method),
            'total' => round((float) $row->grand_total, 2),
            'shipped' => $row->shipped_at ? CarbonImmutable::parse($row->shipped_at)->format('M d, Y') : null,
            'carrier' => (string) ($row->carrier ?? ''),
            'ship_hours' => $row->ship_seconds !== null ? round((float) $row->ship_seconds / 3600, 1) : null,
        ];

        return $paginate
            ? $query->paginate($filters->perPage())->withQueryString()->through($map)
            : $query->get()->map($map)->all();
    }

    /** Portable "seconds from $from to $to" SQL expression. */
    private function secondsBetween(string $from, string $to): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "((julianday({$to}) - julianday({$from})) * 86400)",
            'pgsql' => "EXTRACT(EPOCH FROM ({$to} - {$from}))",
            default => "TIMESTAMPDIFF(SECOND, {$from}, {$to})",
        };
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(ReportFilters $filters, string $view): array
    {
        if ($view === 'orders') {
            return [
                ['Order', 'Placed', 'Customer', 'Email', 'Status', 'Payment', 'Fulfillment', 'Method', 'Total', 'Shipped', 'Carrier', 'Hours to Ship'],
                ...array_map(fn (array $r) => [
                    $r['order_number'], $r['placed'], $r['customer_name'], $r['customer_email'],
                    $r['status']?->label() ?? '', $r['payment_status']?->label() ?? '', $r['fulfillment_status']?->label() ?? '',
                    $r['method'], number_format($r['total'], 2, '.', ''), $r['shipped'] ?? '', $r['carrier'], $r['ship_hours'] ?? '',
                ], $this->orders($filters, false)),
            ];
        }

        return [
            ['Status', 'Orders', 'Share %', 'Order Value'],
            ...array_map(fn (array $s) => [$s['label'], $s['count'], $s['share'] ?? '', number_format($s['value'], 2, '.', '')], array_values($this->statusBreakdown($filters))),
        ];
    }
}
