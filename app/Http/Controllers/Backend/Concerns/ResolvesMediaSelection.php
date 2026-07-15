<?php

namespace App\Http\Controllers\Backend\Concerns;

use App\Models\MediaAsset;
use Illuminate\Http\Request;

trait ResolvesMediaSelection
{
    protected function selectedMediaFilename(Request $request, string $field, string $folder): ?string
    {
        $filename = trim((string) $request->input($field.'_media', ''));

        if ($filename === '') {
            return null;
        }

        return MediaAsset::query()
            ->where('folder', $folder)
            ->where('filename', $filename)
            ->value('filename');
    }
}
