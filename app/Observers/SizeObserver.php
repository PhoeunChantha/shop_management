<?php

namespace App\Observers;

use App\Models\Size;

/**
 * Keeps linked attribute values in sync with their Size master record — the
 * "link" so a rename/code change on a Size propagates to every attribute using it.
 */
class SizeObserver
{
    public function updated(Size $size): void
    {
        if (! $size->wasChanged(['name', 'code'])) {
            return;
        }

        $size->attributeValues()->update([
            'value' => $size->name,
            'code' => $size->code,
        ]);
    }

    public function deleting(Size $size): void
    {
        // Detach linked values into standalone snapshots so nothing dangles.
        $size->attributeValues()->update([
            'source_type' => null,
            'source_id' => null,
        ]);
    }
}
