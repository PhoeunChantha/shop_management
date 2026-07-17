<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

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
}
