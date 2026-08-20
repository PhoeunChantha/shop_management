<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Gateway payment analytics from the `payments` ledger (not order.payment_status):
 * captured / pending / failed transactions, success rate, and method breakdown.
 */
final class PaymentReportService extends ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);
        $base = $this->paymentsBetween($start, $end, $filters);

        $total = (clone $base)->count();
        $capturedCount = (clone $base)->where('status', 'completed')->count();

        return [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => $filters['status'] ?? null,
                'method' => $filters['method'] ?? null,
            ],
            'summary' => [
                'transactions' => $total,
                'captured' => $capturedCount,
                'captured_amount' => (float) (clone $base)->where('status', 'completed')->sum('amount'),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'pending_amount' => (float) (clone $base)->where('status', 'pending')->sum('amount'),
                'failed' => (clone $base)->where('status', 'failed')->count(),
                'success_rate' => $total > 0 ? round(($capturedCount / $total) * 100, 1) : 0.0,
            ],
            'byMethod' => $this->byMethod($start, $end, $filters),
            'transactions' => $this->paginateRows($this->rows($start, $end, $filters), $filters, ['tran_id', 'order', 'method', 'status']),
            'methodOptions' => $this->methodOptions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);

        return $this->rows($start, $end, $filters)
            ->prepend(['Transaction' => 'Transaction', 'Order' => 'Order', 'Method' => 'Method', 'Gateway' => 'Gateway', 'Amount' => 'Amount', 'Status' => 'Status', 'Date' => 'Date'])
            ->map(fn ($row) => array_values($row))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function paymentsBetween(CarbonImmutable $start, CarbonImmutable $end, array $filters): Builder
    {
        return Payment::query()
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->when(filled($filters['status'] ?? null), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(filled($filters['method'] ?? null), fn (Builder $q) => $q->where('payment_option', $filters['method']));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function byMethod(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return $this->paymentsBetween($start, $end, $filters)
            ->selectRaw("COALESCE(NULLIF(payment_option, ''), 'unknown') as method")
            ->selectRaw('COUNT(*) as count')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as captured")
            ->groupBy('method')
            ->orderByDesc('captured')
            ->get()
            ->map(fn (object $row): array => [
                'method' => ucfirst((string) $row->method),
                'count' => (int) $row->count,
                'captured' => (float) $row->captured,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|float>>
     */
    private function rows(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return $this->paymentsBetween($start, $end, $filters)
            ->with('order:id,order_number')
            ->latest('created_at')
            ->limit(5000)
            ->get()
            ->map(fn (Payment $payment): array => [
                'tran_id' => (string) $payment->tran_id,
                'order' => (string) ($payment->order?->order_number ?? '—'),
                'method' => ucfirst((string) ($payment->payment_option ?: 'unknown')),
                'gateway' => ucfirst((string) $payment->gateway),
                'amount' => (float) $payment->amount,
                'status' => ucfirst((string) $payment->status),
                'date' => $payment->created_at?->format('Y-m-d H:i') ?? '',
            ]);
    }

    /**
     * Distinct payment methods present in the ledger, for the filter dropdown.
     *
     * @return array<string, string>
     */
    private function methodOptions(): array
    {
        return Payment::query()
            ->whereNotNull('payment_option')
            ->where('payment_option', '!=', '')
            ->distinct()
            ->orderBy('payment_option')
            ->pluck('payment_option')
            ->mapWithKeys(fn (string $method): array => [$method => ucfirst($method)])
            ->all();
    }
}
