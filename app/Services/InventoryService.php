<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

final class InventoryService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $stock = $filters['stock'] ?? null;

        return Product::query()
            ->with('brand')
            ->withCount('variants')
            ->withCount(['variants as low_variants_count' => fn ($v) => $v->where('low_stock_alert', '>', 0)->whereColumn('stock', '<=', 'low_stock_alert')])
            ->withSum('variants', 'stock')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
            ->when($stock === 'out_of_stock', fn ($q) => $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('product_type', 'single')->where('stock', '<=', 0))
                ->orWhere(fn ($q) => $q->where('product_type', 'variable')->whereDoesntHave('variants', fn ($v) => $v->where('stock', '>', 0)))))
            ->when($stock === 'low_stock', fn ($q) => $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('product_type', 'single')->where('low_stock_alert', '>', 0)->whereColumn('stock', '<=', 'low_stock_alert'))
                ->orWhere(fn ($q) => $q->where('product_type', 'variable')->whereHas('variants', fn ($v) => $v->where('low_stock_alert', '>', 0)->whereColumn('stock', '<=', 'low_stock_alert')))))
            ->when($stock === 'in_stock', fn ($q) => $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('product_type', 'single')->where('stock', '>', 0))
                ->orWhere(fn ($q) => $q->where('product_type', 'variable')->whereHas('variants', fn ($v) => $v->where('stock', '>', 0)))))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function showProduct(string $id): Product
    {
        return Product::with([
            'variants.values',
            'stockMovements.actor',
            'stockMovements.variant.values',
        ])->findOrFail($id);
    }

    public function adjustmentProduct(string $id): Product
    {
        return Product::with('variants')->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: LengthAwarePaginator, stats: array<string, int|float>}
     */
    public function reorderDashboard(array $filters, int $perPage): array
    {
        $rows = $this->reorderRows($filters);
        $page = Paginator::resolveCurrentPage();

        $paginator = new Paginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return [
            'rows' => $paginator->withQueryString(),
            'stats' => [
                'alerts' => $rows->count(),
                'out' => $rows->where('severity', 'out')->count(),
                'low' => $rows->where('severity', 'low')->count(),
                'units' => (int) $rows->sum('suggested_qty'),
                'cost' => (float) $rows->sum(fn (object $row): float => $row->suggested_qty * $row->unit_cost),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    public function updateReorderRules(array $rules): int
    {
        $updated = 0;

        foreach ($rules as $key => $rule) {
            $alert = max(0, (int) ($rule['low_stock_alert'] ?? 0));
            [$kind, $id] = explode(':', (string) $key, 2);

            $model = $kind === 'variant'
                ? ProductVariant::query()->find((int) $id)
                : Product::query()->find((int) $id);

            if (! $model || (int) $model->low_stock_alert === $alert) {
                continue;
            }

            $model->forceFill(['low_stock_alert' => $alert])->save();
            $updated++;
        }

        return $updated;
    }

    /**
     * @param  array<int, string>  $selected
     * @param  array<string, mixed>  $quantities
     * @return array<int, array{stockable: string, quantity_ordered: int, unit_cost: float}>
     */
    public function purchaseOrderItems(array $selected, array $quantities): array
    {
        $alerts = $this->reorderRows([])->keyBy('key');

        return collect($selected)
            ->map(function (string $key) use ($alerts, $quantities): ?array {
                $row = $alerts->get($key);
                if (! $row) {
                    return null;
                }

                $quantity = max(1, (int) ($quantities[$key] ?? $row->suggested_qty));

                return [
                    'stockable' => $key,
                    'quantity_ordered' => $quantity,
                    'unit_cost' => $row->unit_cost,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function resolveStockable(Product $product, ?int $variantId): Model
    {
        if ($variantId) {
            $variant = $product->variants->firstWhere('id', $variantId);

            if (! $variant) {
                throw new \InvalidArgumentException('That variant does not belong to this product.');
            }

            return $variant;
        }

        if ($product->product_type->value !== 'single') {
            throw new \InvalidArgumentException('Pick a variant to adjust for a variable product.');
        }

        return $product;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    private function reorderRows(array $filters): Collection
    {
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));
        $severity = $filters['severity'] ?? null;

        $rows = collect();

        Product::query()
            ->with(['brand:id,name', 'variants.values'])
            ->orderBy('name')
            ->get()
            ->each(function (Product $product) use ($rows): void {
                if ($product->isSingle()) {
                    $this->pushReorderRow($rows, $product, null);
                    return;
                }

                foreach ($product->variants as $variant) {
                    $this->pushReorderRow($rows, $product, $variant);
                }
            });

        return $rows
            ->filter(fn (object $row): bool => $row->alert > 0 && $row->stock <= $row->alert)
            ->when($severity === 'out', fn (Collection $rows): Collection => $rows->where('severity', 'out'))
            ->when($severity === 'low', fn (Collection $rows): Collection => $rows->where('severity', 'low'))
            ->when($search !== '', fn (Collection $rows): Collection => $rows->filter(
                fn (object $row): bool => str_contains(mb_strtolower($row->name.' '.$row->label.' '.$row->sku.' '.$row->brand), $search)
            ))
            ->sortBy([
                fn (object $a, object $b): int => $a->severity_rank <=> $b->severity_rank,
                fn (object $a, object $b): int => $a->stock <=> $b->stock,
                fn (object $a, object $b): int => $a->name <=> $b->name,
            ])
            ->values();
    }

    private function pushReorderRow(Collection $rows, Product $product, ?ProductVariant $variant): void
    {
        $stockable = $variant ?? $product;
        $stock = (int) $stockable->stock;
        $alert = (int) $stockable->low_stock_alert;
        $target = max($alert * 2, $alert + 10);

        $rows->push((object) [
            'key' => ($variant ? 'variant:' : 'product:').$stockable->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'label' => $variant?->variant_label ?: 'Base product',
            'sku' => $stockable->sku ?: $product->sku,
            'brand' => $product->brand?->name ?? 'No brand',
            'thumbnail' => $variant?->image ?: $product->thumbnail,
            'stock' => $stock,
            'alert' => $alert,
            'target' => $target,
            'suggested_qty' => max(1, $target - $stock),
            'unit_cost' => (float) ($stockable->cost_price ?: $product->cost_price ?: 0),
            'severity' => $stock <= 0 ? 'out' : 'low',
            'severity_rank' => $stock <= 0 ? 0 : 1,
        ]);
    }
}
