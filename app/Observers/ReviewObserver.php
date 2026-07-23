<?php

namespace App\Observers;

use App\Models\Review;
use App\Services\ReviewService;

/**
 * Keeps the product rating aggregate in sync whenever a review is saved or removed.
 */
class ReviewObserver
{
    public function __construct(private readonly ReviewService $reviews) {}

    public function saved(Review $review): void
    {
        $this->reviews->recompute($review->product_id);
    }

    public function deleted(Review $review): void
    {
        $this->reviews->recompute($review->product_id);
    }
}
