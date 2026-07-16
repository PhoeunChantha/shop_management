<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MediaUsageService
{
    /**
     * @var array<int, array{label: string, table: string, column: string}>
     */
    private array $references = [
        ['label' => 'Products', 'table' => 'products', 'column' => 'thumbnail'],
        ['label' => 'Product gallery', 'table' => 'product_images', 'column' => 'image'],
        ['label' => 'Product variants', 'table' => 'product_variants', 'column' => 'image'],
        ['label' => 'Categories', 'table' => 'categories', 'column' => 'image'],
        ['label' => 'Brands', 'table' => 'brands', 'column' => 'image'],
        ['label' => 'Banners', 'table' => 'banners', 'column' => 'image'],
        ['label' => 'Collections', 'table' => 'collections', 'column' => 'image'],
        ['label' => 'Color swatches', 'table' => 'colors', 'column' => 'image'],
        ['label' => 'Order items', 'table' => 'order_details', 'column' => 'image'],
        ['label' => 'Settings', 'table' => 'settings', 'column' => 'value'],
    ];

    /**
     * @return array<int, array{label: string, count: int}>
     */
    public function usages(MediaAsset $asset): array
    {
        return collect($this->references)
            ->map(function (array $reference) use ($asset): array {
                return [
                    'label' => $reference['label'],
                    'count' => $this->countReference($asset, $reference['table'], $reference['column']),
                ];
            })
            ->filter(fn (array $usage): bool => $usage['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, MediaAsset>  $assets
     * @return array<int, array{items: array<int, array{label: string, count: int}>, count: int, label: string}>
     */
    public function summaryMap(Collection $assets): array
    {
        return $assets
            ->mapWithKeys(function (MediaAsset $asset): array {
                $items = $this->usages($asset);
                $count = array_sum(array_column($items, 'count'));

                return [
                    $asset->id => [
                        'items' => $items,
                        'count' => $count,
                        'label' => $this->label($items, $count),
                    ],
                ];
            })
            ->all();
    }

    public function isUsed(MediaAsset $asset): bool
    {
        return count($this->usages($asset)) > 0;
    }

    private function countReference(MediaAsset $asset, string $table, string $column): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        $values = array_values(array_filter([
            $asset->filename,
            $asset->path,
            $asset->url,
        ]));

        return DB::table($table)
            ->whereIn($column, $values)
            ->count();
    }

    /**
     * @param  array<int, array{label: string, count: int}>  $items
     */
    private function label(array $items, int $count): string
    {
        if ($count === 0) {
            return 'Unused';
        }

        $first = $items[0]['label'] ?? 'records';
        $extra = max(0, count($items) - 1);

        return $extra > 0
            ? "{$count} use(s) in {$first} +{$extra}"
            : "{$count} use(s) in {$first}";
    }
}
