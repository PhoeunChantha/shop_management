<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\Review;

/**
 * Moderation + denormalised product rating aggregation.
 * `products.rating_avg` / `rating_count` are kept in sync from APPROVED reviews.
 */
final class ReviewService
{
    public function moderate(Review $review, ReviewStatus $status): void
    {
        $review->update(['status' => $status]);
        // The ReviewObserver recomputes the product aggregate on save.
    }

    /**
     * Recalculate a product's rating average + count from its approved reviews.
     */
    public function recompute(int $productId): void
    {
        $agg = Review::query()
            ->where('product_id', $productId)
            ->where('status', ReviewStatus::Approved->value)
            ->selectRaw('COUNT(*) as c, COALESCE(AVG(rating), 0) as a')
            ->first();

        Product::whereKey($productId)->update([
            'rating_count' => (int) ($agg->c ?? 0),
            'rating_avg' => round((float) ($agg->a ?? 0), 2),
        ]);
    }
}
