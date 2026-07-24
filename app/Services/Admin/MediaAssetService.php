<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ImageManager;
use App\Models\MediaAsset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

final class MediaAssetService
{
    public function __construct(
        private readonly MediaOptimizationService $optimizer,
        private readonly MediaUsageService $usage,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return MediaAsset::query()
            ->with('user:id,name')
            ->search(trim((string) ($filters['search'] ?? '')))
            ->when($filters['folder'] ?? null, fn ($query, string $folder) => $query->where('folder', $folder))
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
            'totalSize' => MediaAsset::sum('size'),
            'totalAssets' => MediaAsset::count(),
            'optimizedAssets' => MediaAsset::whereIn('optimization_status', ['optimized', 'kept_original'])->count(),
            'totalSaved' => MediaAsset::query()
                ->whereColumn('original_size', '>', 'optimized_size')
                ->selectRaw('COALESCE(SUM(original_size - optimized_size), 0) as saved')
                ->value('saved') ?: 0,
        ];
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, MediaAsset>
     */
    public function store(array $files, string $folder, ?string $altText, ?int $userId): Collection
    {
        return collect($files)->map(function (UploadedFile $file) use ($folder, $altText, $userId): MediaAsset {
            $dimensions = @getimagesize($file->getRealPath()) ?: null;
            $mimeType = $file->getMimeType();
            $size = $file->getSize() ?: 0;
            $filename = ImageManager::upload($file, $folder);

            $asset = MediaAsset::create([
                'user_id' => $userId,
                'folder' => $folder,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mimeType,
                'size' => $size,
                'original_size' => $size,
                'optimized_size' => $size,
                'width' => $dimensions[0] ?? null,
                'height' => $dimensions[1] ?? null,
                'optimization_status' => 'pending',
                'alt_text' => $altText,
            ]);

            $asset->update($this->optimizer->optimize($asset));

            return $asset;
        });
    }

    public function picker(string $folder, ?string $search): Collection
    {
        return MediaAsset::query()
            ->where('folder', $folder)
            ->search(trim((string) $search))
            ->latest()
            ->limit(48)
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

        $assets->each(function (MediaAsset $asset): void {
            $asset->update($this->optimizer->optimize($asset));
        });

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
        ImageManager::delete($media->filename, $media->folder);
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
            'name' => $asset->original_name ?: $asset->filename,
            'url' => $asset->url,
            'thumbnail_url' => $asset->thumbnail_url,
            'size' => $asset->size_for_humans,
            'original_size' => $asset->original_size_for_humans,
            'optimized_size' => $asset->optimized_size_for_humans,
            'optimization_status' => $asset->optimization_status,
            'optimization_label' => $asset->optimization_label,
            'dimensions' => $asset->width && $asset->height ? $asset->width.'x'.$asset->height : null,
        ];
    }
}
