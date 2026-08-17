<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
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

    protected function perPage(array $filters, int $default = 25): int
    {
        return (int) ($filters['per_page'] ?? $default);
    }

    /**
     * Turn an in-memory report collection into a searchable, paginated result —
     * used for the report tables so exports can still return the full dataset.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $searchKeys
     * @return LengthAwarePaginatorContract<int, array<string, mixed>>
     */
    protected function paginateRows(Collection $rows, array $filters, array $searchKeys, int $default = 25): LengthAwarePaginatorContract
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = $rows->filter(function (array $row) use ($needle, $searchKeys): bool {
                foreach ($searchKeys as $key) {
                    if (str_contains(mb_strtolower((string) ($row[$key] ?? '')), $needle)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        $perPage = $this->perPage($filters, $default);
        $page = Paginator::resolveCurrentPage();
        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($items, $rows->count(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]);
    }
}
