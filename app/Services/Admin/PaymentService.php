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

    /**
     * @return array<string, int|float>
     */
    public function stats(): array
    {
        return [
            'total' => Payment::count(),
            'completed' => Payment::where('status', 'completed')->count(),
            'collected' => (float) Payment::where('status', 'completed')->sum('amount'),
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
