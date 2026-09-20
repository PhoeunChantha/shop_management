<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\MediaAsset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class MediaAssetService
{
    /**
     * Grid sort options, keyed by the value the filter form submits.
     */
    public const SORTS = [
        'newest' => 'Newest first',
        'oldest' => 'Oldest first',
        'largest' => 'Largest files',
        'smallest' => 'Smallest files',
        'name' => 'Name (A–Z)',
        'unused' => 'Least used',
    ];

    public const KINDS = [
        'image' => 'Photos',
        'vector' => 'Vectors (SVG)',
        'animated' => 'Animated (GIF)',
        'other' => 'Other files',
    ];

    public const USAGE_STATES = [
        'used' => 'In use',
        'unused' => 'Unused',
    ];

    public function __construct(
        private readonly MediaOptimizationService $optimizer,
        private readonly MediaUsageService $usage,
        private readonly MediaStorageService $storage,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->with('user:id,name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, int|float>
     */
    public function stats(): array
    {
        return [
            'totalSize' => MediaAsset::sum('size'),
            'totalAssets' => MediaAsset::count(),
            'optimizedAssets' => MediaAsset::whereIn('optimization_status', ['optimized', 'kept_original'])->count(),
            'pendingAssets' => MediaAsset::whereIn('optimization_status', ['pending', 'failed'])->count(),
            'unusedAssets' => MediaAsset::where('usage_count', 0)->whereNotNull('usage_synced_at')->count(),
            'totalSaved' => MediaAsset::query()
                ->whereColumn('original_size', '>', 'optimized_size')
                ->selectRaw('COALESCE(SUM(original_size - optimized_size), 0) as saved')
                ->value('saved') ?: 0,
        ];
    }

    /**
     * Store uploads, skipping any file already in the same folder (matched on
     * a SHA-1 of its contents) so re-uploading never duplicates storage.
     *
     * @param  array<int, UploadedFile>  $files
     * @param  array<string, mixed>  $meta
     * @return array{created: Collection<int, MediaAsset>, duplicates: Collection<int, MediaAsset>}
     */
    public function store(array $files, string $folder, array $meta, ?int $userId): array
    {
        $created = collect();
        $duplicates = collect();

        foreach ($files as $file) {
            $checksum = @sha1_file($file->getRealPath()) ?: null;
            $existing = $checksum ? $this->findDuplicate($checksum, $folder) : null;

            if ($existing) {
                $duplicates->push($existing);

                continue;
            }

            $created->push($this->persist($file, $folder, $meta, $userId, $checksum));
        }

        return ['created' => $created, 'duplicates' => $duplicates];
    }

    /**
     * Editable metadata — the fields that matter for SEO and findability.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(MediaAsset $asset, array $data): MediaAsset
    {
        $asset->update([
            'title' => $data['title'] ?? null,
            'alt_text' => $data['alt_text'] ?? null,
            'tags' => $this->normalizeTags($data['tags'] ?? null),
            'folder' => $data['folder'] ?? $asset->folder,
        ]);

        return $asset->refresh();
    }

    /**
     * Apply a bulk action to a set of assets.
     *
     * @param  array<int, int>  $ids
     * @return array{action: string, affected: int, blocked: int}
     */
    public function bulk(string $action, array $ids, ?string $folder = null): array
    {
        $assets = MediaAsset::whereIn('id', $ids)->get();

        if ($assets->isEmpty()) {
            return ['action' => $action, 'affected' => 0, 'blocked' => 0];
        }

        return match ($action) {
            'delete' => $this->bulkDelete($assets),
            'move' => $this->bulkMove($assets, (string) $folder),
            'optimize' => $this->bulkOptimize($assets),
            default => ['action' => $action, 'affected' => 0, 'blocked' => 0],
        };
    }

    /**
     * Assets offered by the in-form picker.
     *
     * The whole library is offered regardless of folder — an image uploaded for
     * a product can be picked as a banner or a brand logo. A folder is only a
     * sort preference, floating that folder's assets to the top.
     */
    public function picker(?string $folder, ?string $search): Collection
    {
        return MediaAsset::query()
            ->search(trim((string) $search))
            ->when($folder, fn (Builder $query, string $folder) => $query->orderByRaw(
                'CASE WHEN folder = ? THEN 0 ELSE 1 END', [$folder],
            ))
            ->latest()
            ->limit(96)
            ->get()
            ->map(fn (MediaAsset $asset): array => $this->payload($asset));
    }

    public function optimizePending(): int
    {
        $assets = MediaAsset::query()
            ->whereIn('optimization_status', ['pending', 'failed'])
            ->oldest()
            ->limit(100)
            ->get();

        $assets->each(fn (MediaAsset $asset) => $asset->update($this->optimizer->optimize($asset)));

        return $assets->count();
    }

    public function delete(MediaAsset $media): void
    {
        $usages = $this->usage->usages($media);

        if ($usages !== []) {
            $labels = collect($usages)
                ->map(fn (array $usage): string => $usage['label'].' ('.$usage['count'].')')
                ->join(', ');

            throw new \InvalidArgumentException('This media file is still used by '.$labels.'. Remove those references before deleting it.');
        }

        $this->optimizer->deleteThumbnail($media);
        $this->storage->delete($media);
        $media->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(MediaAsset $asset): array
    {
        return [
            'id' => $asset->id,
            'filename' => $asset->filename,
            // What a consuming field stores — portable across every folder.
            'value' => $asset->reference,
            'folder' => $asset->folder,
            'name' => $asset->display_name,
            'url' => $asset->url,
            'thumbnail_url' => $asset->thumbnail_url,
            'size' => $asset->size_for_humans,
            'original_size' => $asset->original_size_for_humans,
            'optimized_size' => $asset->optimized_size_for_humans,
            'optimization_status' => $asset->optimization_status,
            'optimization_label' => $asset->optimization_label,
            'alt_text' => $asset->alt_text,
            'storage' => $asset->storage_label,
            'dimensions' => $asset->width && $asset->height ? $asset->width.'x'.$asset->height : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filtered(array $filters): Builder
    {
        return MediaAsset::query()
            ->search(trim((string) ($filters['search'] ?? '')))
            ->ofKind($filters['kind'] ?? null)
            ->when($filters['folder'] ?? null, fn (Builder $query, string $folder) => $query->where('folder', $folder))
            ->when(
                ($filters['usage'] ?? null) === 'used',
                fn (Builder $query) => $query->where('usage_count', '>', 0),
            )
            ->when(
                ($filters['usage'] ?? null) === 'unused',
                fn (Builder $query) => $query->where('usage_count', 0),
            )
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->tap(fn (Builder $query) => $this->applySort($query, $filters['sort'] ?? 'newest'));
    }

    private function applySort(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'oldest' => $query->oldest(),
            'largest' => $query->orderByDesc('size'),
            'smallest' => $query->orderBy('size'),
            'name' => $query->orderByRaw('COALESCE(NULLIF(title, ""), original_name, filename) asc'),
            'unused' => $query->orderBy('usage_count')->latest(),
            default => $query->latest(),
        };
    }

    private function findDuplicate(string $checksum, string $folder): ?MediaAsset
    {
        return MediaAsset::where('checksum', $checksum)->where('folder', $folder)->first();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function persist(UploadedFile $file, string $folder, array $meta, ?int $userId, ?string $checksum): MediaAsset
    {
        $dimensions = @getimagesize($file->getRealPath()) ?: null;
        $mimeType = $file->getMimeType();
        $size = $file->getSize() ?: 0;
        $originalName = $file->getClientOriginalName();

        $stored = $this->storage->store($file, $folder);

        $asset = MediaAsset::create([
            'user_id' => $userId,
            'folder' => $folder,
            'disk' => $stored['disk'],
            'filename' => $stored['filename'],
            'object_key' => $stored['object_key'],
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => $size,
            'original_size' => $size,
            'optimized_size' => $size,
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'optimization_status' => 'pending',
            'alt_text' => $meta['alt_text'] ?? null,
            'title' => $meta['title'] ?? null,
            'tags' => $this->normalizeTags($meta['tags'] ?? null),
            'checksum' => $checksum,
            'usage_count' => 0,
            'usage_synced_at' => now(),
        ]);

        $asset->update($this->optimizer->optimize($asset));

        return $asset;
    }

    /**
     * @param  Collection<int, MediaAsset>  $assets
     * @return array{action: string, affected: int, blocked: int}
     */
    private function bulkDelete(Collection $assets): array
    {
        $usage = $this->usage->summaryMap($assets);
        $affected = 0;
        $blocked = 0;

        foreach ($assets as $asset) {
            if (($usage[$asset->id]['count'] ?? 0) > 0) {
                $blocked++;

                continue;
            }

            $this->optimizer->deleteThumbnail($asset);
            $this->storage->delete($asset);
            $asset->delete();
            $affected++;
        }

        return ['action' => 'delete', 'affected' => $affected, 'blocked' => $blocked];
    }

    /**
     * Moving is a re-filing of the library folder only — the stored file does
     * not move, so every existing reference to it keeps resolving.
     *
     * @param  Collection<int, MediaAsset>  $assets
     * @return array{action: string, affected: int, blocked: int}
     */
    private function bulkMove(Collection $assets, string $folder): array
    {
        $affected = DB::transaction(function () use ($assets, $folder): int {
            $moved = 0;

            foreach ($assets as $asset) {
                if ($asset->folder === $folder) {
                    continue;
                }

                $asset->forceFill(['folder' => $folder])->save();
                $moved++;
            }

            return $moved;
        });

        return ['action' => 'move', 'affected' => $affected, 'blocked' => 0];
    }

    /**
     * @param  Collection<int, MediaAsset>  $assets
     * @return array{action: string, affected: int, blocked: int}
     */
    private function bulkOptimize(Collection $assets): array
    {
        foreach ($assets as $asset) {
            $asset->update($this->optimizer->optimize($asset));
        }

        return ['action' => 'optimize', 'affected' => $assets->count(), 'blocked' => 0];
    }

    /**
     * @param  array<int, string>|string|null  $tags
     * @return array<int, string>|null
     */
    private function normalizeTags(array|string|null $tags): ?array
    {
        $values = collect(is_string($tags) ? explode(',', $tags) : ($tags ?? []))
            ->map(fn ($tag): string => trim((string) $tag))
            ->filter()
            ->unique()
            ->take(12)
            ->values()
            ->all();

        return $values === [] ? null : $values;
    }
}
