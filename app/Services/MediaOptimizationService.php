<?php

namespace App\Services;

use App\Helpers\ImageManager;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MediaOptimizationService
{
    private const MAX_THUMBNAIL_WIDTH = 520;
    private const MAX_THUMBNAIL_HEIGHT = 390;

    /**
     * @return array<string, mixed>
     */
    public function optimize(MediaAsset $asset): array
    {
        $path = ImageManager::path($asset->filename, $asset->folder);

        if (! $path) {
            return $this->skipped('Missing file path.');
        }

        $absolutePath = public_path($path);

        if (! File::exists($absolutePath)) {
            return $this->skipped('Original file was not found.');
        }

        $extension = Str::lower(pathinfo($asset->filename, PATHINFO_EXTENSION));
        $originalSize = File::size($absolutePath);
        $dimensions = @getimagesize($absolutePath) ?: null;

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return [
                'status' => 'skipped',
                'notes' => strtoupper($extension ?: 'file').' files are stored without raster optimization.',
                'original_size' => $originalSize,
                'optimized_size' => $originalSize,
                'size' => $originalSize,
                'width' => $dimensions[0] ?? $asset->width,
                'height' => $dimensions[1] ?? $asset->height,
                'thumbnail_filename' => null,
            ];
        }

        try {
            $image = $this->createImage($absolutePath, $extension);

            if (! $image) {
                return $this->failed('This image format could not be opened by GD.', $originalSize, $dimensions);
            }

            $optimized = $this->writeOptimized($image, $absolutePath, $extension);
            $thumbnail = $this->writeThumbnail($image, $asset, $dimensions);

            imagedestroy($image);

            $optimizedSize = File::size($absolutePath);

            return [
                'status' => $optimized ? 'optimized' : 'kept_original',
                'notes' => $optimized ? 'Compressed and thumbnail generated.' : 'Original was already smaller than the optimized output.',
                'original_size' => $originalSize,
                'optimized_size' => $optimizedSize,
                'size' => $optimizedSize,
                'width' => $dimensions[0] ?? $asset->width,
                'height' => $dimensions[1] ?? $asset->height,
                'thumbnail_filename' => $thumbnail,
            ];
        } catch (\Throwable $e) {
            return $this->failed($e->getMessage(), $originalSize, $dimensions);
        }
    }

    public function deleteThumbnail(MediaAsset $asset): void
    {
        if (! $asset->thumbnail_filename) {
            return;
        }

        $path = ImageManager::path($asset->thumbnail_filename, $asset->folder);

        if ($path && File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
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

        File::replace($path, File::get($tempPath));
        File::delete($tempPath);

        return true;
    }

    /**
     * @param  array<int, int>|null  $dimensions
     */
    private function writeThumbnail(mixed $image, MediaAsset $asset, ?array $dimensions): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $sourceWidth = $dimensions[0] ?? imagesx($image);
        $sourceHeight = $dimensions[1] ?? imagesy($image);

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            return null;
        }

        $ratio = min(self::MAX_THUMBNAIL_WIDTH / $sourceWidth, self::MAX_THUMBNAIL_HEIGHT / $sourceHeight, 1);
        $targetWidth = max(1, (int) round($sourceWidth * $ratio));
        $targetHeight = max(1, (int) round($sourceHeight * $ratio));

        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        $baseName = pathinfo($asset->filename, PATHINFO_FILENAME);
        $thumbnailName = 'thumbs/'.$baseName.'.webp';
        $thumbnailPath = ImageManager::path($thumbnailName, $asset->folder);

        if (! $thumbnailPath) {
            imagedestroy($thumbnail);

            return null;
        }

        File::ensureDirectoryExists(dirname(public_path($thumbnailPath)));
        $written = imagewebp($thumbnail, public_path($thumbnailPath), 80);
        imagedestroy($thumbnail);

        return $written ? $thumbnailName : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function skipped(string $notes): array
    {
        return [
            'status' => 'skipped',
            'notes' => $notes,
        ];
    }

    /**
     * @param  array<int, int>|null  $dimensions
     * @return array<string, mixed>
     */
    private function failed(string $notes, int $size, ?array $dimensions): array
    {
        return [
            'status' => 'failed',
            'notes' => Str::limit($notes, 240),
            'original_size' => $size,
            'optimized_size' => $size,
            'size' => $size,
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'thumbnail_filename' => null,
        ];
    }
}
