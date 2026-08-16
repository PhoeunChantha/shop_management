<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\ReturnRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Returns & refunds activity in a date range, keyed on requested_at.
 */
final class ReturnReportService extends ReportService
{
    private const LIST_LIMIT = 100;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);
        $base = $this->returnsBetween($start, $end, $filters);

        return [
            'filters' => $this->appliedFilters($start, $end, $filters),
            'summary' => [
                'total' => (clone $base)->count(),
                'refunded' => (clone $base)->where('refund_status', 'refunded')->count(),
                'refund_amount' => (float) (clone $base)->whereIn('refund_status', ['partial', 'refunded'])->sum('refund_amount'),
                'pending' => (clone $base)->whereIn('status', ['requested', 'approved', 'received'])->count(),
            ],
            'byStatus' => $this->byStatus($start, $end, $filters),
            'returns' => $this->rows($start, $end, $filters),
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
            ->prepend(['Return #' => 'Return #', 'Order' => 'Order', 'Customer' => 'Customer', 'Reason' => 'Reason', 'Status' => 'Status', 'Refund Status' => 'Refund Status', 'Refund' => 'Refund', 'Requested' => 'Requested'])
            ->map(fn ($row) => array_values($row))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function returnsBetween(CarbonImmutable $start, CarbonImmutable $end, array $filters): Builder
    {
        return ReturnRequest::query()
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->whereBetween('requested_at', [$start->startOfDay(), $end->endOfDay()]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int>>
     */
    private function byStatus(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return $this->returnsBetween($start, $end, $filters)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get()
            ->map(fn (object $row): array => [
                'label' => ReturnRequest::STATUSES[$row->status] ?? ucfirst((string) $row->status),
                'count' => (int) $row->count,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|float>>
     */
    private function rows(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return $this->returnsBetween($start, $end, $filters)
            ->with(['order:id,order_number', 'user:id,name,email'])
            ->latest('requested_at')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (ReturnRequest $return): array => [
                'return_number' => (string) $return->return_number,
                'order' => (string) ($return->order?->order_number ?? '—'),
                'customer' => (string) ($return->user?->name ?? 'Guest'),
                'reason' => $return->reasonLabel(),
                'status' => $return->statusLabel(),
                'refund_status' => $return->refundStatusLabel(),
                'refund_amount' => (float) $return->refund_amount,
                'requested_at' => $return->requested_at?->format('Y-m-d') ?? '',
            ]);
    }
}
