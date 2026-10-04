<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Helpers\ImageManager;
use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One place that knows WHERE media-library files live.
 *
 * Two backends are supported side by side so a library can be migrated without
 * a rewrite of every consumer:
 *
 *  - "local"  — public/uploads/{folder}/{file} via App\Helpers\ImageManager.
 *               This is what every pre-existing asset uses.
 *  - a disk   — any filesystem disk (config/media.php → MEDIA_DISK), e.g. "r2"
 *               for Cloudflare R2. Objects are written to
 *               {prefix}/{folder}/{file} and the asset stores the resulting
 *               PUBLIC URL as its filename.
 *
 * Storing the public URL as the filename is deliberate: ImageManager::url()
 * passes absolute URLs straight through, so products, banners, brands and
 * every other consumer keep resolving remote assets with no change at all.
 */
final class MediaStorageService
{
    public function defaultDisk(): string
    {
        return (string) config('media.disk', 'local');
    }

    public function isRemote(?string $disk = null): bool
    {
        return ($disk ?? $this->defaultDisk()) !== 'local';
    }

    /**
     * Write an upload to the configured backend.
     *
     * @return array{disk: string, filename: string, object_key: string|null}
     */
    public function store(UploadedFile $file, string $folder): array
    {
        $disk = $this->defaultDisk();

        if (! $this->isRemote($disk)) {
            return [
                'disk' => 'local',
                'filename' => ImageManager::upload($file, $folder),
                'object_key' => null,
            ];
        }

        $key = $this->key($folder, $this->name($file->getClientOriginalExtension() ?: $file->extension()));

        Storage::disk($disk)->put($key, File::get($file->getRealPath()), [
            'visibility' => 'public',
            'ContentType' => $file->getMimeType() ?: 'application/octet-stream',
        ]);

        return [
            'disk' => $disk,
            'filename' => $this->publicUrl($disk, $key),
            'object_key' => $key,
        ];
    }

    /**
     * Upload a locally generated derivative (thumbnail) for a remote asset.
     *
     * @return array{filename: string, object_key: string}|null
     */
    public function storeDerivative(MediaAsset $asset, string $localPath, string $suffix = 'thumb'): ?array
    {
        if (! $asset->isRemote() || ! File::exists($localPath)) {
            return null;
        }

        $extension = Str::lower(pathinfo($localPath, PATHINFO_EXTENSION)) ?: 'webp';
        $key = $this->key($asset->folder, $suffix.'/'.$this->name($extension));

        Storage::disk($asset->disk)->put($key, File::get($localPath), [
            'visibility' => 'public',
            'ContentType' => $extension === 'webp' ? 'image/webp' : 'application/octet-stream',
        ]);

        return [
            'filename' => $this->publicUrl($asset->disk, $key),
            'object_key' => $key,
        ];
    }

    /**
     * Pull a remote asset into a temp file so GD can work on it. Local assets
     * return their existing public path. Returns null when the file is gone.
     */
    public function localCopy(MediaAsset $asset): ?string
    {
        if (! $asset->isRemote()) {
            $path = ImageManager::path($asset->filename, $asset->folder);

            return $path && File::exists(public_path($path)) ? public_path($path) : null;
        }

        if (! $asset->object_key || ! Storage::disk($asset->disk)->exists($asset->object_key)) {
            return null;
        }

        $temp = $this->tempPath(pathinfo($asset->object_key, PATHINFO_EXTENSION) ?: 'tmp');
        File::put($temp, Storage::disk($asset->disk)->get($asset->object_key));

        return $temp;
    }

    /**
     * Push a temp file back over a remote asset's object, then drop the temp.
     */
    public function pushLocalCopy(MediaAsset $asset, string $localPath): void
    {
        if (! $asset->isRemote() || ! $asset->object_key || ! File::exists($localPath)) {
            return;
        }

        Storage::disk($asset->disk)->put($asset->object_key, File::get($localPath), [
            'visibility' => 'public',
            'ContentType' => $asset->mime_type ?: 'application/octet-stream',
        ]);
    }

    public function discardLocalCopy(MediaAsset $asset, ?string $localPath): void
    {
        if ($localPath && $asset->isRemote() && File::exists($localPath)) {
            File::delete($localPath);
        }
    }

    /**
     * Remove an asset's original and thumbnail from whichever backend holds it.
     */
    public function delete(MediaAsset $asset): void
    {
        if (! $asset->isRemote()) {
            ImageManager::delete($asset->filename, $asset->folder);

            return;
        }

        $keys = array_values(array_filter([$asset->object_key, $asset->thumbnail_object_key]));

        if ($keys !== []) {
            Storage::disk($asset->disk)->delete($keys);
        }
    }

    /**
     * Absolute public URL for a stored object. Disks configured with a bucket
     * URL (R2/S3) already return one; disks that return a site-relative path
     * are promoted to absolute, because the asset stores this string as its
     * filename and ImageManager only passes ABSOLUTE urls through untouched.
     */
    public function publicUrl(string $disk, string $key): string
    {
        $url = rtrim(Storage::disk($disk)->url($key), '/');

        return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
    }

    /**
     * Round-trip a tiny probe object through R2 with the given credentials:
     * write it, fetch it from the public URL, then delete it. Uses a disk built
     * on the fly so freshly saved .env values are tested, not the booted config.
     *
     * @param  array{bucket: string, endpoint: string, url: string, key: string, secret: string}  $r2
     * @return array{ok: bool, message: string}
     */
    public function testR2(array $r2): array
    {
        $missing = array_keys(array_filter($r2, fn (string $value): bool => $value === ''));

        if ($missing !== []) {
            return ['ok' => false, 'message' => __('Save all R2 fields first (missing: :fields).', ['fields' => implode(', ', $missing)])];
        }

        $disk = Storage::build([
            'driver' => 's3',
            'key' => $r2['key'],
            'secret' => $r2['secret'],
            'region' => 'auto',
            'bucket' => $r2['bucket'],
            'endpoint' => $r2['endpoint'],
            'url' => $r2['url'],
            'use_path_style_endpoint' => true,
            'throw' => true,
        ]);

        $prefix = trim((string) config('media.prefix', ''), '/');
        $key = ltrim($prefix.'/.connection-test-'.Str::random(8).'.txt', '/');

        try {
            $disk->put($key, 'ok');
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => __('Could not write to the bucket — check the endpoint, bucket name and API token. (:error)', ['error' => Str::limit($e->getMessage(), 160)])];
        }

        try {
            $public = Http::timeout(10)->get($r2['url'].'/'.$key);
        } catch (\Throwable) {
            $public = null;
        } finally {
            rescue(fn () => $disk->delete($key), report: false);
        }

        if (! $public?->successful()) {
            return ['ok' => false, 'message' => __('Upload works, but files are not reachable at the public URL. Enable a custom domain or r2.dev URL under bucket → Settings → Public access.')];
        }

        return ['ok' => true, 'message' => __('R2 connected — upload, public read and delete all work.')];
    }

    private function key(string $folder, string $name): string
    {
        $prefix = trim((string) config('media.prefix', ''), '/');
        $folder = trim($folder, '/');

        return ltrim($prefix.'/'.$folder.'/'.$name, '/');
    }

    private function name(?string $extension): string
    {
        $extension = Str::lower((string) $extension) ?: 'bin';

        return now()->format('Y-m-d').'-'.Str::lower(Str::random(8)).'.'.$extension;
    }

    private function tempPath(string $extension): string
    {
        $directory = storage_path('app/media-tmp');
        File::ensureDirectoryExists($directory);

        return $directory.DIRECTORY_SEPARATOR.Str::lower(Str::random(12)).'.'.Str::lower($extension);
    }
}
