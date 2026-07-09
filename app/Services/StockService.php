<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Applies stock changes and records an immutable movement for every one, so the
 * on-hand quantity always has an audit trail.
 */
final class StockService
{
    /**
     * Apply a signed delta to a stockable (single Product or ProductVariant),
     * clamp at zero, and log the movement. Returns the recorded movement.
     */
    public function adjust(Product|ProductVariant $item, int $delta, StockMovementType $type, ?string $note = null): StockMovement
    {
        return DB::transaction(function () use ($item, $delta, $type, $note) {
            $current = (int) $item->stock;
            $new = max(0, $current + $delta);

            $item->stock = $new;
            $item->save();

            $isVariant = $item instanceof ProductVariant;

            return StockMovement::create([
                'product_id' => $isVariant ? $item->product_id : $item->id,
                'variant_id' => $isVariant ? $item->id : null,
                'type' => $type,
                'quantity' => $new - $current, // actual applied delta after clamping
                'stock_after' => $new,
                'note' => $note,
                'user_id' => Auth::id(),
            ]);
        });
    }
}
