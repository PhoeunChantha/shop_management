<?php

namespace App\Observers;

use App\Models\Color;

/**
 * Keeps linked attribute values in sync with their Color master record, so a
 * rename / code / swatch change propagates to every attribute using it.
 */
class ColorObserver
{
    public function updated(Color $color): void
    {
        if (! $color->wasChanged(['name', 'code', 'hex_code'])) {
            return;
        }

        $color->attributeValues()->update([
            'value' => $color->name,
            'code' => $color->code,
            'color_hex' => $color->hex_code,
        ]);
    }

    public function deleting(Color $color): void
    {
        $color->attributeValues()->update([
            'source_type' => null,
            'source_id' => null,
        ]);
    }
}
