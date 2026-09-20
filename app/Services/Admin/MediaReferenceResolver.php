<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\MediaAsset;

/**
 * Turns a media-library pick submitted by a form into the value to store.
 *
 * The library is shared: one image can be a product thumbnail, a banner, a
 * brand logo and the site logo at the same time. So a pick is resolved across
 * the WHOLE library — its folder is only an organizing label — and what comes
 * back is the asset's portable reference (an absolute URL for remote assets, a
 * "uploads/{folder}/{file}" path for local ones), which ImageManager resolves
 * without knowing the consuming field's folder.
 *
 * This is the single implementation; controllers (via ResolvesMediaSelection),
 * ProductService and SettingService all delegate here so a fix lands once.
 */
final class MediaReferenceResolver
{
    public function resolve(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $asset = MediaAsset::query()
            ->where(function ($query) use ($value) {
                $query->where('filename', $value)->orWhere('object_key', $value);
            })
            ->first();

        // A value already stored as a public path ("uploads/media/foo.jpg")
        // never matches the bare filename column, so match on the basename and
        // confirm against the candidate's own reference.
        if (! $asset && str_contains($value, '/')) {
            $asset = MediaAsset::query()
                ->where('filename', basename($value))
                ->get()
                ->first(fn (MediaAsset $candidate): bool => $candidate->reference === $value);
        }

        return $asset?->reference;
    }
}
