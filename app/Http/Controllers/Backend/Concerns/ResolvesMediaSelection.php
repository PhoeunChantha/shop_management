<?php

namespace App\Http\Controllers\Backend\Concerns;

use App\Services\Admin\MediaReferenceResolver;
use Illuminate\Http\Request;

trait ResolvesMediaSelection
{
    /**
     * Resolve the media-library asset a form picked into the value this field
     * should store.
     *
     * A library asset is NOT owned by one feature: the same image can be a
     * product thumbnail, a banner and a brand logo at the same time. So the
     * pick is looked up across the WHOLE library (its folder is only an
     * organizing label) and what comes back is the asset's portable reference
     * — an absolute URL for remote assets, a "uploads/{folder}/{file}" path for
     * local ones. ImageManager resolves both without knowing the caller's
     * folder, which is what makes one image reusable everywhere.
     *
     * @param  string  $folder  The field's own folder — kept for the signature
     *                          every caller already uses, no longer a filter.
     */
    protected function selectedMediaFilename(Request $request, string $field, string $folder): ?string
    {
        return app(MediaReferenceResolver::class)
            ->resolve((string) $request->input($field.'_media', ''));
    }
}
