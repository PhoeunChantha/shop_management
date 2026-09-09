<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Reusable image handling for controllers.
 *
 * Only the file NAME is stored in the database (e.g. "ab12cd.jpg"); files live
 * under public/uploads/{folder}/. Always pass the same {folder} for a field.
 *
 *   $model->image = ImageManager::upload($request->file('image'), 'categories'); // stores name only
 *   <img src="{{ ImageManager::url($model->image, 'categories') }}">
 */
final class ImageManager
{
    /**
     * Base folder (under public/) that all uploads are stored in.
     */
    private const ROOT = 'uploads';

    /**
     * Store an uploaded file under uploads/{folder}/ and return only its filename.
     */
    public static function upload(UploadedFile $file, string $folder): string
    {
        $directory = self::directory($folder);

        $extension = strtolower($file->getClientOriginalExtension() ?: ($file->extension() ?: 'bin'));
        $filename = now()->format('Y-m-d').'-'.Str::lower(Str::random(8)).'.'.$extension;

        File::ensureDirectoryExists(public_path($directory));
        $file->move(public_path($directory), $filename);

        return $filename;
    }

    /**
     * Replace a stored file. Returns the new filename, or the old one if no file is given.
     */
    public static function update(?UploadedFile $file, ?string $oldName, string $folder): ?string
    {
        if (! $file instanceof UploadedFile) {
            return $oldName;
        }

        self::delete($oldName, $folder);

        return self::upload($file, $folder);
    }

    /**
     * Delete a stored file by name. Safe to call with null.
     */
    public static function delete(?string $name, string $folder): void
    {
        if (empty($name)) {
            return;
        }

        if (self::isExternalUrl($name)) {
            return;
        }

        $path = self::directory($folder).'/'.$name;

        if (File::exists(public_path($path))) {
            File::delete(public_path($path));
        }

        // Clean up the paired responsive thumbnail (see generateThumbnail()),
        // if one was ever generated for this file — otherwise it's orphaned
        // forever under thumbs/, since nothing else references it by name.
        $thumbPath = self::path(self::thumbnailName($name), $folder);

        if ($thumbPath && File::exists(public_path($thumbPath))) {
            File::delete(public_path($thumbPath));
        }
    }

    /**
     * Public-relative path for a stored file (e.g. "uploads/categories/ab12cd.jpg"), or null.
     */
    public static function path(?string $name, string $folder): ?string
    {
        if (empty($name)) {
            return null;
        }

        if (self::isExternalUrl($name)) {
            return $name;
        }

        return self::directory($folder).'/'.$name;
    }

    /**
     * Full asset() URL for a stored file, or null.
     */
    public static function url(?string $name, string $folder): ?string
    {
        if (empty($name)) {
            return null;
        }

        if (self::isExternalUrl($name)) {
            return $name;
        }

        $path = self::path($name, $folder);

        return $path ? asset($path) : null;
    }

    /**
     * Generate (or regenerate) a compressed WebP thumbnail for a stored image,
     * saved alongside it as "thumbs/{basename}.webp" under the same folder.
     * Used to power responsive <img srcset> without re-fetching the full-size
     * original. Returns the thumbnail's stored filename (relative to the
     * folder), or null if GD/WebP support is unavailable or the source can't
     * be read — callers should treat that as "no thumbnail yet" and fall back
     * to the full-size image, never as a hard failure.
     */
    public static function generateThumbnail(string $filename, string $folder, int $maxWidth, int $maxHeight): ?string
    {
        if (! function_exists('imagewebp') || self::isExternalUrl($filename)) {
            return null;
        }

        $path = self::path($filename, $folder);
        $absolutePath = $path ? public_path($path) : null;

        if (! $absolutePath || ! File::exists($absolutePath)) {
            return null;
        }

        $extension = Str::lower(pathinfo($filename, PATHINFO_EXTENSION));
        $image = match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($absolutePath),
            'png' => @imagecreatefrompng($absolutePath),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : false,
            default => false,
        };

        if (! $image) {
            return null;
        }

        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            imagedestroy($image);

            return null;
        }

        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $targetWidth = max(1, (int) round($sourceWidth * $ratio));
        $targetHeight = max(1, (int) round($sourceHeight * $ratio));

        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        imagedestroy($image);

        $thumbnailName = self::thumbnailName($filename);
        $thumbnailPath = self::path($thumbnailName, $folder);

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
     * URL of a previously generated thumbnail (see generateThumbnail()), or
     * null when none exists yet — callers fall back to the full-size url().
     */
    public static function thumbnailUrl(?string $filename, string $folder): ?string
    {
        if (empty($filename) || self::isExternalUrl($filename)) {
            return null;
        }

        $path = self::path(self::thumbnailName($filename), $folder);

        return $path && File::exists(public_path($path)) ? asset($path) : null;
    }

    private static function thumbnailName(string $filename): string
    {
        return 'thumbs/'.pathinfo($filename, PATHINFO_FILENAME).'.webp';
    }

    private static function isExternalUrl(string $name): bool
    {
        return Str::startsWith($name, ['http://', 'https://']);
    }

    /**
     * Resolve a folder to a path under "uploads/" without double-prefixing.
     */
    private static function directory(string $folder): string
    {
        $folder = trim($folder, '/');

        if ($folder === '') {
            return self::ROOT;
        }

        if ($folder === self::ROOT || str_starts_with($folder, self::ROOT.'/')) {
            return $folder;
        }

        return self::ROOT.'/'.$folder;
    }
}
