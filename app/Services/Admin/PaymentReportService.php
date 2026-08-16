<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class PaymentReportService extends ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);
        $mix = $this->paymentMix($start, $end, $filters);

        return [
            'filters' => $this->appliedFilters($start, $end, $filters),
            'summary' => [
                'orders' => (int) $mix->sum('count'),
                'collected' => (float) $mix->where('is_paid', true)->sum('amount'),
                'outstanding' => (float) $mix->where('is_paid', false)->sum('amount'),
            ],
            'paymentMix' => $mix,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);

        return $this->paymentMix($start, $end, $filters)
            ->map(fn (array $row): array => [
                'status' => $row['label'],
                'orders' => $row['count'],
                'amount' => $row['amount'],
            ])
            ->prepend(['status' => 'Payment Status', 'orders' => 'Orders', 'amount' => 'Amount'])
            ->map(fn ($row) => array_values($row))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function paymentMix(CarbonImmutable $start, CarbonImmutable $end, array $filters): Collection
    {
        $paid = $this->paidStatuses();

        return $this->ordersBetween($start, $end, $filters)
            ->selectRaw('payment_status')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(grand_total) as amount')
            ->groupBy('payment_status')
            ->orderByDesc('count')
            ->get()
            ->map(function (object $row) use ($paid): array {
                $status = $row->payment_status instanceof PaymentStatus
                    ? $row->payment_status->value
                    : (string) $row->payment_status;

                return [
                    'status' => $status,
                    'label' => PaymentStatus::tryFrom($status)?->label() ?? ucfirst($status),
                    'count' => (int) $row->count,
                    'amount' => (float) $row->amount,
                    'is_paid' => in_array($status, $paid, true),
                ];
            });
    }
}
