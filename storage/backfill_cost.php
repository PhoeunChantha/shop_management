<?php

use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductVariant;

$updated = 0;

OrderDetail::whereNull('unit_cost')->chunkById(500, function ($rows) use (&$updated): void {
    foreach ($rows as $detail) {
        $cost = null;

        if ($detail->product_variant_id) {
            $cost = ProductVariant::where('id', $detail->product_variant_id)->value('cost_price');
        }

        if ($cost === null && $detail->product_id) {
            $cost = Product::where('id', $detail->product_id)->value('cost_price');
        }

        $detail->update(['unit_cost' => $cost ?? 0]);
        $updated++;
    }
});

echo "Backfilled unit_cost on {$updated} order lines.".PHP_EOL;
