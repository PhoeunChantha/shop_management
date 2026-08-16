<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\PurchaseOrder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Purchasing / supplier spend in a date range, keyed on the PO order date.
 */
final class PurchasingReportService extends ReportService
{
    private const LIST_LIMIT = 50;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);
        $base = $this->purchasesBetween($start, $end);

        return [
            'filters' => $this->appliedFilters($start, $end, $filters),
            'summary' => [
                'orders' => (clone $base)->count(),
                'total_cost' => (float) (clone $base)->whereIn('status', ['ordered', 'partial', 'received'])->sum('subtotal'),
                'received' => (clone $base)->where('status', 'received')->count(),
                'pending' => (clone $base)->whereIn('status', ['ordered', 'partial'])->count(),
            ],
            'supplierSpend' => $this->supplierSpend($start, $end),
            'purchaseOrders' => $this->rows($start, $end),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);

        return $this->rows($start, $end)
            ->prepend(['PO Number' => 'PO Number', 'Supplier' => 'Supplier', 'Status' => 'Status', 'Ordered' => 'Ordered', 'Subtotal' => 'Subtotal'])
            ->map(fn ($row) => array_values($row))
            ->all();
    }

    private function purchasesBetween(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return PurchaseOrder::query()
            ->whereBetween(DB::raw('DATE(COALESCE(ordered_at, created_at))'), [$start->toDateString(), $end->toDateString()]);
    }

    /**
     * @return Collection<int, array<string, string|float>>
     */
    private function supplierSpend(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return $this->purchasesBetween($start, $end)
            ->with('supplier:id,name')
            ->whereIn('status', ['ordered', 'partial', 'received'])
            ->get()
            ->groupBy(fn (PurchaseOrder $order): string => $order->supplier?->name ?? 'Unknown supplier')
            ->map(fn (Collection $group, string $supplier): array => [
                'supplier' => $supplier,
                'orders' => $group->count(),
                'spend' => (float) $group->sum('subtotal'),
            ])
            ->sortByDesc('spend')
            ->values();
    }

    /**
     * @return Collection<int, array<string, string|float>>
     */
    private function rows(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return $this->purchasesBetween($start, $end)
            ->with('supplier:id,name')
            ->latest('created_at')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (PurchaseOrder $order): array => [
                'po_number' => (string) $order->po_number,
                'supplier' => (string) ($order->supplier?->name ?? 'Unknown supplier'),
                'status' => $order->statusLabel(),
                'ordered_at' => $order->ordered_at?->format('Y-m-d') ?? $order->created_at->format('Y-m-d'),
                'subtotal' => (float) $order->subtotal,
            ]);
    }
}
