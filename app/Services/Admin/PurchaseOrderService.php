<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class PurchaseOrderService
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    /** Expected-arrival quick filters for the PO list (key => label). */
    public const EXPECTED_FILTERS = [
        'overdue' => 'Overdue',
        'week' => 'Next 7 days',
        'month' => 'Next 30 days',
        'none' => 'No date set',
    ];

    /** PO total buckets for the list filter ("min-max" key => label; open-ended max allowed). */
    public const AMOUNT_RANGES = [
        '0-500' => 'Under $500',
        '500-1000' => '$500 – $1,000',
        '1000-2500' => '$1,000 – $2,500',
        '2500-5000' => '$2,500 – $5,000',
        '5000-' => '$5,000+',
    ];

    /** Sort options for the PO list (key => label). */
    public const SORTS = [
        'newest' => 'Newest first',
        'oldest' => 'Oldest first',
        'expected' => 'Expected soonest',
        'total_desc' => 'Highest total',
        'total_asc' => 'Lowest total',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $today = now()->toDateString();

        return PurchaseOrder::query()
            ->with('supplier:id,name')
            ->withCount('items')
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status))
            ->when($filters['supplier_id'] ?? null, fn (Builder $query, $supplier) => $query->where('supplier_id', $supplier))
            // Order date: the day the PO was placed, falling back to creation for drafts.
            ->when($filters['date_from'] ?? null, fn (Builder $query, $from) => $query->whereDate(DB::raw('COALESCE(ordered_at, created_at)'), '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $to) => $query->whereDate(DB::raw('COALESCE(ordered_at, created_at)'), '<=', $to))
            ->when($filters['expected'] ?? null, fn (Builder $query, $expected) => match ($expected) {
                'overdue' => $query->whereDate('expected_at', '<', $today)->whereNotIn('status', ['received', 'cancelled']),
                'week' => $query->whereBetween('expected_at', [$today, now()->addDays(7)->toDateString()]),
                'month' => $query->whereBetween('expected_at', [$today, now()->addDays(30)->toDateString()]),
                'none' => $query->whereNull('expected_at'),
                default => $query,
            })
            ->when($filters['amount'] ?? null, function (Builder $query, string $range): void {
                [$min, $max] = array_pad(explode('-', $range, 2), 2, '');
                if ($min !== '') {
                    $query->where('subtotal', '>=', (float) $min);
                }
                if ($max !== '') {
                    $query->where('subtotal', '<', (float) $max);
                }
            })
            ->tap(fn (Builder $query) => match ($filters['sort'] ?? 'newest') {
                'oldest' => $query->oldest(),
                'expected' => $query->orderByRaw('expected_at IS NULL')->orderBy('expected_at')->latest(),
                'total_desc' => $query->orderByDesc('subtotal')->latest(),
                'total_asc' => $query->orderBy('subtotal')->latest(),
                default => $query->latest(),
            })
            ->paginate($perPage)
            ->withQueryString();
    }

    public function stats(): array
    {
        return [
            'total' => PurchaseOrder::count(),
            'open' => PurchaseOrder::whereIn('status', ['draft', 'ordered', 'partial'])->count(),
            'received' => PurchaseOrder::where('status', 'received')->count(),
            'value' => (float) PurchaseOrder::whereIn('status', ['ordered', 'partial', 'received'])->sum('subtotal'),
        ];
    }

    public function supplierOptions(): Collection
    {
        return Supplier::query()->where('status', true)->orderBy('name')->get(['id', 'name']);
    }

    public function stockableOptions(): array
    {
        return array_map(fn (array $meta): string => $meta['label'], $this->stockableMeta());
    }

    /**
     * Every purchasable product / variant keyed by its stockable token, with the
     * label shown in the picker plus the facts the PO form uses to pre-fill unit
     * cost and show on-hand stock next to each line.
     *
     * @return array<string, array{label: string, name: string, sku: string, stock: int, cost: float|null}>
     */
    public function stockableMeta(): array
    {
        $meta = [];

        Product::query()
            ->with(['variants.values'])
            ->orderBy('name')
            ->get(['id', 'name', 'product_type', 'sku', 'stock', 'cost_price'])
            ->each(function (Product $product) use (&$meta): void {
                if ($product->product_type->value === 'single') {
                    $sku = (string) ($product->sku ?: '');
                    $meta['product:'.$product->id] = [
                        'label' => $product->name.($sku !== '' ? ' · '.$sku : ''),
                        'name' => (string) $product->name,
                        'sku' => $sku,
                        'stock' => (int) $product->stock,
                        'cost' => $product->cost_price !== null ? (float) $product->cost_price : null,
                    ];

                    return;
                }

                foreach ($product->variants as $variant) {
                    $sku = (string) ($variant->sku ?: '');
                    $label = $product->name.' / '.($variant->variant_label ?: 'Variant');
                    $meta['variant:'.$variant->id] = [
                        'label' => $label.($sku !== '' ? ' · '.$sku : ''),
                        'name' => $label,
                        'sku' => $sku,
                        'stock' => (int) $variant->stock,
                        'cost' => $variant->cost_price !== null ? (float) $variant->cost_price : null,
                    ];
                }
            });

        return $meta;
    }

    public function findForShow(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        return $purchaseOrder->load(['supplier', 'creator:id,name,email', 'items.product', 'items.variant.values']);
    }

    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data): PurchaseOrder {
            $items = $this->normalizedItems($data['items'] ?? []);
            if ($items === []) {
                throw new \InvalidArgumentException('Add at least one product or variant to the purchase order.');
            }

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $this->number(),
                'supplier_id' => $data['supplier_id'],
                'user_id' => auth()->id(),
                'status' => $data['status'] ?? 'draft',
                'ordered_at' => ($data['status'] ?? 'draft') === 'ordered' ? now()->toDateString() : null,
                'expected_at' => $data['expected_at'] ?? null,
                'subtotal' => array_sum(array_column($items, 'line_total')),
                'notes' => $data['notes'] ?? null,
            ]);

            $purchaseOrder->items()->createMany($items);

            return $purchaseOrder->fresh(['supplier', 'items']);
        });
    }

    public function markOrdered(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft purchase orders can be marked ordered.');
        }

        $purchaseOrder->update([
            'status' => 'ordered',
            'ordered_at' => now()->toDateString(),
        ]);
    }

    public function cancel(PurchaseOrder $purchaseOrder): void
    {
        if (in_array($purchaseOrder->status, ['received', 'cancelled'], true)) {
            throw new \InvalidArgumentException('This purchase order cannot be cancelled.');
        }

        $purchaseOrder->update(['status' => 'cancelled']);
    }

    public function receive(PurchaseOrder $purchaseOrder): void
    {
        if (! in_array($purchaseOrder->status, ['ordered', 'partial'], true)) {
            throw new \InvalidArgumentException('Only ordered purchase orders can be received.');
        }

        DB::transaction(function () use ($purchaseOrder): void {
            $purchaseOrder->load('items');

            foreach ($purchaseOrder->items as $item) {
                $remaining = $item->remaining();
                if ($remaining < 1) {
                    continue;
                }

                $stockable = $item->variant_id
                    ? ProductVariant::findOrFail($item->variant_id)
                    : Product::findOrFail($item->product_id);

                $this->stock->adjust(
                    $stockable,
                    $remaining,
                    StockMovementType::Restock,
                    'Received '.$purchaseOrder->po_number,
                );

                $item->update(['quantity_received' => $item->quantity_ordered]);
            }

            $purchaseOrder->update([
                'status' => 'received',
                'received_at' => now()->toDateString(),
            ]);
        });
    }

    private function number(): string
    {
        do {
            $number = 'PO-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (PurchaseOrder::where('po_number', $number)->exists());

        return $number;
    }

    private function normalizedItems(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            if (blank($row['stockable'] ?? null)) {
                continue;
            }

            [$kind, $id] = explode(':', (string) $row['stockable'], 2);
            $quantity = (int) ($row['quantity_ordered'] ?? 0);
            $unitCost = (float) ($row['unit_cost'] ?? 0);
            if ($quantity < 1) {
                continue;
            }

            $stockable = $kind === 'variant'
                ? ProductVariant::with('product')->findOrFail((int) $id)
                : Product::findOrFail((int) $id);

            $isVariant = $stockable instanceof ProductVariant;
            $product = $isVariant ? $stockable->product : $stockable;
            $label = $isVariant ? $product->name.' / '.($stockable->variant_label ?: 'Variant') : $product->name;

            $items[] = [
                'product_id' => $product->id,
                'variant_id' => $isVariant ? $stockable->id : null,
                'name' => $label,
                'sku' => $stockable->sku ?: $product->sku,
                'quantity_ordered' => $quantity,
                'quantity_received' => 0,
                'unit_cost' => $unitCost,
                'line_total' => round($quantity * $unitCost, 2),
            ];
        }

        return $items;
    }
}
