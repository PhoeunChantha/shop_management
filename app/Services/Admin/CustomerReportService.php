<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class CustomerReportService extends ReportService
{
    private const LIMIT = 50;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);
        $customers = $this->customerSpend($start, $end, $filters);

        return [
            'filters' => $this->appliedFilters($start, $end, $filters),
            'summary' => [
                'customers' => $customers->count(),
                'repeat' => $customers->where('orders', '>', 1)->count(),
                'revenue' => (float) $customers->sum('spend'),
            ],
            'customers' => $customers,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);

        return $this->customerSpend($start, $end, $filters)
            ->prepend(['Customer' => 'Customer', 'Email' => 'Email', 'Orders' => 'Orders', 'Spend' => 'Spend'])
            ->map(fn ($row) => array_values($row))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, string|int|float>>
     */
    private function customerSpend(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        return $this->ordersBetween($start, $end, $filters)
            ->whereIn('payment_status', $this->paidStatuses())
            ->selectRaw('customer_name, customer_email')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(grand_total) as spend')
            ->groupBy('customer_name', 'customer_email')
            ->orderByDesc('spend')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (object $row): array => [
                'customer_name' => (string) $row->customer_name,
                'customer_email' => (string) $row->customer_email,
                'orders' => (int) $row->orders,
                'spend' => (float) $row->spend,
            ]);
    }
}
