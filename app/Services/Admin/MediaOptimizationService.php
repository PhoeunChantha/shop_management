<?php

namespace App\Services\Admin;

use App\Helpers\ImageManager;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaOptimizationService
{
    private const MAX_THUMBNAIL_WIDTH = 520;

    private const MAX_THUMBNAIL_HEIGHT = 390;

    public function __construct(
        private readonly MediaStorageService $storage,
    ) {}

    /**
     * Compress an asset in place and generate its thumbnail. The returned array
     * is column-keyed so callers can hand it straight to update().
     *
     * Remote (R2/S3) assets are pulled to a temp file, processed with GD, and
     * pushed back; local assets are processed in public/uploads directly.
     *
     * @return array<string, mixed>
     */
    public function optimize(MediaAsset $asset): array
    {
        $workingPath = $this->storage->localCopy($asset);

        if (! $workingPath) {
            return $this->skipped('Original file was not found.');
        }

        try {
            return $this->process($asset, $workingPath);
        } catch (\Throwable $e) {
            return $this->failed($e->getMessage(), (int) $asset->size, null);
        } finally {
            $this->storage->discardLocalCopy($asset, $workingPath);
        }
    }

    public function deleteThumbnail(MediaAsset $asset): void
    {
        if (! $asset->thumbnail_filename) {
            return;
        }

        if ($asset->isRemote()) {
            if ($asset->thumbnail_object_key) {
                Storage::disk($asset->disk)->delete($asset->thumbnail_object_key);
            }

            return;
        }

        $path = ImageManager::path($asset->thumbnail_filename, $asset->folder);

        if ($path && File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function process(MediaAsset $asset, string $workingPath): array
    {
        $extension = Str::lower(pathinfo($asset->isRemote() ? (string) $asset->object_key : $asset->filename, PATHINFO_EXTENSION));
        $originalSize = File::size($workingPath);
        $dimensions = @getimagesize($workingPath) ?: null;

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return [
                'optimization_status' => 'skipped',
                'optimization_notes' => strtoupper($extension ?: 'file').' files are stored without raster optimization.',
                'original_size' => $originalSize,
                'optimized_size' => $originalSize,
                'size' => $originalSize,
                'width' => $dimensions[0] ?? $asset->width,
                'height' => $dimensions[1] ?? $asset->height,
            ];
        }

        $image = $this->createImage($workingPath, $extension);

        if (! $image) {
            return $this->failed('This image format could not be opened by GD.', $originalSize, $dimensions);
        }

        $optimized = $this->writeOptimized($image, $workingPath, $extension);
        imagedestroy($image);

        if ($optimized) {
            $this->storage->pushLocalCopy($asset, $workingPath);
        }

        $thumbnail = $this->makeThumbnail($asset, $workingPath);
        $optimizedSize = File::size($workingPath);

        return array_merge([
            'optimization_status' => $optimized ? 'optimized' : 'kept_original',
            'optimization_notes' => $optimized
                ? 'Compressed and thumbnail generated.'
                : 'Original was already smaller than the optimized output.',
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'size' => $optimizedSize,
            'width' => $dimensions[0] ?? $asset->width,
            'height' => $dimensions[1] ?? $asset->height,
        ], $thumbnail);
    }

    /**
     * @return array<string, string|null>
     */
    private function makeThumbnail(MediaAsset $asset, string $workingPath): array
    {
        if (! $asset->isRemote()) {
            return [
                'thumbnail_filename' => ImageManager::generateThumbnail(
                    $asset->filename,
                    $asset->folder,
                    self::MAX_THUMBNAIL_WIDTH,
                    self::MAX_THUMBNAIL_HEIGHT,
                ),
            ];
        }

        $thumbPath = $this->resizeToTemp($workingPath);

        if (! $thumbPath) {
            return ['thumbnail_filename' => null, 'thumbnail_object_key' => null];
        }

        $stored = $this->storage->storeDerivative($asset, $thumbPath);
        File::delete($thumbPath);

        return [
            'thumbnail_filename' => $stored['filename'] ?? null,
            'thumbnail_object_key' => $stored['object_key'] ?? null,
        ];
    }

    /**
     * Downscale an image to a temp .webp for remote upload. Returns null when
     * GD lacks WebP support or the source can't be read — callers treat that
     * as "no thumbnail", never as a failure.
     */
    private function resizeToTemp(string $sourcePath): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $extension = Str::lower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        $image = $this->createImage($sourcePath, $extension);

        if (! $image) {
            return null;
        }

        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            imagedestroy($image);

            return null;
        }

        $ratio = min(self::MAX_THUMBNAIL_WIDTH / $sourceWidth, self::MAX_THUMBNAIL_HEIGHT / $sourceHeight, 1);
        $targetWidth = max(1, (int) round($sourceWidth * $ratio));
        $targetHeight = max(1, (int) round($sourceHeight * $ratio));

        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        imagedestroy($image);

        $target = dirname($sourcePath).DIRECTORY_SEPARATOR.pathinfo($sourcePath, PATHINFO_FILENAME).'-thumb.webp';
        $written = imagewebp($thumbnail, $target, 80);
        imagedestroy($thumbnail);

        return $written ? $target : null;
    }

    private function createImage(string $path, string $extension): mixed
    {
        return match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private function writeOptimized(mixed $image, string $path, string $extension): bool
    {
        $tempPath = $path.'.optimized';
        $written = match ($extension) {
            'jpg', 'jpeg' => imagejpeg($image, $tempPath, 82),
            'png' => imagepng($image, $tempPath, 8),
            'webp' => function_exists('imagewebp') && imagewebp($image, $tempPath, 82),
            default => false,
        };

        if (! $written || ! File::exists($tempPath)) {
            File::delete($tempPath);

            return false;
        }

        if (File::size($tempPath) >= File::size($path)) {
            File::delete($tempPath);

            return false;
        }

        try {
            // Best-effort: replacing the original with its compressed version
            // can transiently fail (e.g. a brief OS-level file lock). That
            // should degrade to "keep the original", not abort optimize()
            // entirely — thumbnail generation doesn't depend on this step.
            File::replace($path, File::get($tempPath));
        } catch (\Throwable) {
            File::delete($tempPath);

            return false;
        }

        File::delete($tempPath);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function skipped(string $notes): array
    {
        return [
            'optimization_status' => 'skipped',
            'optimization_notes' => $notes,
        ];
    }

    /**
     * @param  array<int, int>|null  $dimensions
     * @return array<string, mixed>
     */
    private function failed(string $notes, int $size, ?array $dimensions): array
    {
        return [
            'optimization_status' => 'failed',
            'optimization_notes' => Str::limit($notes, 240),
            'original_size' => $size,
            'optimized_size' => $size,
            'size' => $size,
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
        ];
    }
}
