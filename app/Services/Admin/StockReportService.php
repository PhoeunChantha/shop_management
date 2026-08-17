<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Stock is a live snapshot, not a dated event, so this report ignores the date
 * range: it aggregates on-hand quantity and valuation across single products
 * and variants, and lists everything at or below its reorder threshold.
 */
final class StockReportService extends ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = []): array
    {
        $single = DB::table('products')
            ->where('product_type', 'single')
            ->selectRaw('COUNT(*) as skus')
            ->selectRaw('COALESCE(SUM(stock), 0) as units')
            ->selectRaw('COALESCE(SUM(stock * COALESCE(cost_price, 0)), 0) as value')
            ->selectRaw('SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as out_count')
            ->selectRaw('SUM(CASE WHEN stock > 0 AND stock <= low_stock_alert THEN 1 ELSE 0 END) as low_count')
            ->first();

        $variant = DB::table('product_variants')
            ->selectRaw('COUNT(*) as skus')
            ->selectRaw('COALESCE(SUM(stock), 0) as units')
            ->selectRaw('COALESCE(SUM(stock * COALESCE(cost_price, 0)), 0) as value')
            ->selectRaw('SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as out_count')
            ->selectRaw('SUM(CASE WHEN stock > 0 AND stock <= low_stock_alert THEN 1 ELSE 0 END) as low_count')
            ->first();

        return [
            'summary' => [
                'skus' => (int) $single->skus + (int) $variant->skus,
                'units' => (int) $single->units + (int) $variant->units,
                'value' => (float) $single->value + (float) $variant->value,
                'low' => (int) $single->low_count + (int) $variant->low_count,
                'out' => (int) $single->out_count + (int) $variant->out_count,
            ],
            'lowStock' => $this->paginateRows($this->lowStockRows(), $filters, ['name', 'sku']),
        ];
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function exportRows(): array
    {
        return $this->lowStockRows()
            ->prepend(['Item' => 'Item', 'SKU' => 'SKU', 'Stock' => 'Stock', 'Threshold' => 'Threshold', 'Unit Cost' => 'Unit Cost', 'Value' => 'Value', 'Status' => 'Status'])
            ->map(fn (array $row) => [
                $row['name'] ?? $row['Item'],
                $row['sku'] ?? $row['SKU'],
                $row['stock'] ?? $row['Stock'],
                $row['threshold'] ?? $row['Threshold'],
                $row['unit_cost'] ?? $row['Unit Cost'],
                $row['value'] ?? $row['Value'],
                $row['severity'] ?? $row['Status'],
            ])
            ->all();
    }

    /**
     * Items at or below their reorder threshold, worst first.
     *
     * @return Collection<int, array<string, string|int|float>>
     */
    private function lowStockRows(): Collection
    {
        $products = Product::query()
            ->where('product_type', 'single')
            ->whereColumn('stock', '<=', 'low_stock_alert')
            ->orderBy('stock')
            ->limit(5000)
            ->get()
            ->map(fn (Product $product): array => [
                'name' => (string) $product->name,
                'sku' => (string) ($product->sku ?? ''),
                'stock' => (int) $product->stock,
                'threshold' => (int) $product->low_stock_alert,
                'unit_cost' => (float) ($product->cost_price ?? 0),
                'value' => (int) $product->stock * (float) ($product->cost_price ?? 0),
                'severity' => $product->stock <= 0 ? 'Out of stock' : 'Low',
            ]);

        $variants = ProductVariant::query()
            ->with('product:id,name')
            ->whereColumn('stock', '<=', 'low_stock_alert')
            ->orderBy('stock')
            ->limit(5000)
            ->get()
            ->map(fn (ProductVariant $variant): array => [
                'name' => trim(((string) ($variant->product?->name ?? 'Variant')).' — '.((string) $variant->sku)),
                'sku' => (string) $variant->sku,
                'stock' => (int) $variant->stock,
                'threshold' => (int) $variant->low_stock_alert,
                'unit_cost' => (float) ($variant->cost_price ?? 0),
                'value' => (int) $variant->stock * (float) ($variant->cost_price ?? 0),
                'severity' => $variant->stock <= 0 ? 'Out of stock' : 'Low',
            ]);

        return $products->merge($variants)
            ->sortBy('stock')
            ->values();
    }
}
