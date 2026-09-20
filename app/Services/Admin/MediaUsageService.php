<?php

namespace App\Services\Admin;

use App\Models\MediaAsset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Answers "is this media file still referenced anywhere?".
 *
 * Reference lookups are BATCHED: one query per reference table for a whole
 * page of assets, instead of one query per table per asset (which was 11
 * queries × 24 cards = 264 queries for a single grid render).
 */
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
        ['label' => 'Deals', 'table' => 'deal_campaigns', 'column' => 'image'],
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
        return $this->summaryMap(collect([$asset]))[$asset->id]['items'] ?? [];
    }

    /**
     * Usage summary for a page of assets, keyed by asset id.
     *
     * @param  Collection<int, MediaAsset>  $assets
     * @return array<int, array{items: array<int, array{label: string, count: int}>, count: int, label: string}>
     */
    public function summaryMap(Collection $assets): array
    {
        if ($assets->isEmpty()) {
            return [];
        }

        // Every string an asset could be stored as, mapped back to its id.
        $lookup = [];

        foreach ($assets as $asset) {
            foreach ($this->candidateValues($asset) as $value) {
                $lookup[$value] = $asset->id;
            }
        }

        $counts = [];

        foreach ($this->references as $reference) {
            foreach ($this->countReferences($reference, array_keys($lookup)) as $value => $count) {
                $id = $lookup[$value] ?? null;

                if ($id === null) {
                    continue;
                }

                $counts[$id][$reference['label']] = ($counts[$id][$reference['label']] ?? 0) + $count;
            }
        }

        return $assets
            ->mapWithKeys(function (MediaAsset $asset) use ($counts): array {
                $items = collect($counts[$asset->id] ?? [])
                    ->map(fn (int $count, string $label): array => ['label' => $label, 'count' => $count])
                    ->values()
                    ->all();

                $total = array_sum(array_column($items, 'count'));

                return [
                    $asset->id => [
                        'items' => $items,
                        'count' => $total,
                        'label' => $this->label($items, $total),
                    ],
                ];
            })
            ->all();
    }

    public function isUsed(MediaAsset $asset): bool
    {
        return $this->usages($asset) !== [];
    }

    /**
     * Refresh the cached usage_count on a set of assets so the grid can filter
     * and sort on "unused" without re-scanning every reference table.
     *
     * @param  Collection<int, MediaAsset>  $assets
     */
    public function syncCounts(Collection $assets): void
    {
        if ($assets->isEmpty()) {
            return;
        }

        $map = $this->summaryMap($assets);
        $now = now();

        foreach ($assets as $asset) {
            $count = $map[$asset->id]['count'] ?? 0;

            if ($asset->usage_count === $count && $asset->usage_synced_at !== null) {
                continue;
            }

            $asset->forceFill(['usage_count' => $count, 'usage_synced_at' => $now])->saveQuietly();
        }
    }

    /**
     * Every stored form of an asset: bare filename, public-relative path and
     * absolute URL. Remote assets store the URL as their filename, so the
     * first value already covers them.
     *
     * @return array<int, string>
     */
    private function candidateValues(MediaAsset $asset): array
    {
        return array_values(array_unique(array_filter([
            $asset->filename,
            $asset->path,
            $asset->url,
        ])));
    }

    /**
     * @param  array{label: string, table: string, column: string}  $reference
     * @param  array<int, string>  $values
     * @return array<string, int>
     */
    private function countReferences(array $reference, array $values): array
    {
        if ($values === [] || ! Schema::hasTable($reference['table']) || ! Schema::hasColumn($reference['table'], $reference['column'])) {
            return [];
        }

        return DB::table($reference['table'])
            ->select($reference['column'].' as value', DB::raw('COUNT(*) as total'))
            ->whereIn($reference['column'], $values)
            ->groupBy($reference['column'])
            ->pluck('total', 'value')
            ->map(fn ($total): int => (int) $total)
            ->all();
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
