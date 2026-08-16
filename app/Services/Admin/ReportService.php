<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Shared plumbing for the admin report pages: date-range resolution and the
 * common order query. Each concrete report service extends this so the range
 * and filter semantics stay identical across every report surface.
 */
abstract class ReportService
{
    /**
     * Payment states that count as realised revenue.
     *
     * @return array<int, string>
     */
    protected function paidStatuses(): array
    {
        return [
            PaymentStatus::Paid->value,
            PaymentStatus::PartiallyRefunded->value,
            PaymentStatus::Refunded->value,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function dateRange(array $filters): array
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
     * Orders placed within the range, honouring the optional status filters.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function ordersBetween(CarbonImmutable $start, CarbonImmutable $end, array $filters): Builder
    {
        return Order::query()
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['payment_status'] ?? null), fn (Builder $query) => $query->where('payment_status', $filters['payment_status']))
            ->whereBetween(DB::raw('DATE(COALESCE(placed_at, created_at))'), [$start->toDateString(), $end->toDateString()]);
    }

    /**
     * Normalise applied filters for echoing back into the view.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, string|null>
     */
    protected function appliedFilters(CarbonImmutable $start, CarbonImmutable $end, array $filters): array
    {
        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => $filters['status'] ?? null,
            'payment_status' => $filters['payment_status'] ?? null,
        ];
    }
}
