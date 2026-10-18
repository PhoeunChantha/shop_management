<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class PaymentService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->query($filters)
            ->with('order:id,order_number,customer_name,customer_email')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Gateway transaction statuses (value => label). */
    public const STATUSES = ['pending' => 'Pending', 'completed' => 'Completed', 'failed' => 'Failed'];

    /**
     * Headline numbers for the stat cards, scoped to the active filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, int|float>
     */
    public function stats(array $filters = []): array
    {
        $row = $this->query($filters)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as collected")
            ->toBase()
            ->first();

        return [
            'total' => (int) $row->total,
            'completed' => (int) $row->completed,
            'pending' => (int) $row->pending,
            'failed' => (int) $row->failed,
            'collected' => (float) $row->collected,
        ];
    }

    /**
     * Choices for the filter selects, taken from recorded transactions.
     *
     * @return array{gateways: array<string, string>, options: array<string, string>, currencies: array<string, string>}
     */
    public function filterOptions(): array
    {
        $distinct = fn (string $column, callable $label): array => Payment::query()
            ->whereNotNull($column)->where($column, '!=', '')
            ->distinct()->orderBy($column)->pluck($column)
            ->mapWithKeys(fn (string $value): array => [$value => $label($value)])
            ->all();

        return [
            'gateways' => $distinct('gateway', fn (string $v): string => strtoupper($v)),
            'options' => $distinct('payment_option', fn (string $v): string => strtoupper($v)),
            'currencies' => $distinct('currency', fn (string $v): string => strtoupper($v)),
        ];
    }

    /**
     * Amount-range filter options: value ("min-max", open-ended max) => label.
     *
     * @return array<string, string>
     */
    public static function amountRanges(): array
    {
        return [
            '0-10' => '0 – 10',
            '10-50' => '10 – 50',
            '50-200' => '50 – 200',
            '200-' => '200+',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  resource  $handle
     */
    public function writeCsv(array $filters, $handle): void
    {
        fputcsv($handle, ['Order', 'Gateway', 'Transaction ID', 'Option', 'Amount', 'Currency', 'Status', 'Date']);

        $this->query($filters)
            ->with('order:id,order_number')
            ->latest()
            ->chunk(500, function ($payments) use ($handle): void {
                foreach ($payments as $payment) {
                    fputcsv($handle, [
                        $payment->order?->order_number,
                        $payment->gateway,
                        $payment->tran_id,
                        $payment->payment_option,
                        number_format((float) $payment->amount, 2, '.', ''),
                        $payment->currency,
                        $payment->status,
                        $payment->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Payment>
     */
    private function query(array $filters): Builder
    {
        return Payment::query()
            ->when(
                filled($filters['status'] ?? null),
                fn (Builder $q) => $q->where('status', $filters['status']),
            )
            ->when($filters['gateway'] ?? null, fn (Builder $q, string $v) => $q->where('gateway', $v))
            ->when($filters['payment_option'] ?? null, fn (Builder $q, string $v) => $q->where('payment_option', $v))
            ->when($filters['currency'] ?? null, fn (Builder $q, string $v) => $q->where('currency', $v))
            ->when($filters['amount'] ?? null, function (Builder $q, string $range): void {
                [$min, $max] = array_pad(explode('-', $range), 2, '');
                if ($min !== '') {
                    $q->where('amount', '>=', (float) $min);
                }
                if ($max !== '') {
                    $q->where('amount', '<=', (float) $max);
                }
            })
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $v) => $q->whereDate('created_at', '<=', $v))
            ->when(
                filled($filters['search'] ?? null),
                function (Builder $q) use ($filters): void {
                    $term = trim((string) $filters['search']);
                    $q->where(function (Builder $q) use ($term): void {
                        $q->where('tran_id', 'like', "%{$term}%")
                            ->orWhereHas('order', fn (Builder $o) => $o->where('order_number', 'like', "%{$term}%")
                                ->orWhere('customer_email', 'like', "%{$term}%"));
                    });
                },
            );
    }
}
