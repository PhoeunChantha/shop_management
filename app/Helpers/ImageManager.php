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

        $path = self::directory($folder).'/'.$name;

        if (File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
    }

    /**
     * Public-relative path for a stored file (e.g. "uploads/categories/ab12cd.jpg"), or null.
     */
    public static function path(?string $name, string $folder): ?string
    {
        return empty($name) ? null : self::directory($folder).'/'.$name;
    }

    /**
     * Full asset() URL for a stored file, or null.
     */
    public static function url(?string $name, string $folder): ?string
    {
        $path = self::path($name, $folder);

        return $path ? asset($path) : null;
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
