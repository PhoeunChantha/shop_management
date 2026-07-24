<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Moderation + denormalised product rating aggregation.
 * `products.rating_avg` / `rating_count` are kept in sync from APPROVED reviews.
 */
final class ReviewService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return Review::query()
            ->with(['product:id,name,thumbnail', 'user:id,name'])
            ->search(trim((string) ($filters['search'] ?? '')))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['rating'] ?? null, fn ($q, $v) => $q->where('rating', $v))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function counts(): Collection
    {
        return Review::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
    }

    public function moderate(Review $review, ReviewStatus $status): void
    {
        $review->update(['status' => $status]);
        // The ReviewObserver recomputes the product aggregate on save.
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function bulkModerate(array $ids, ReviewStatus $status): int
    {
        $productIds = Review::whereKey($ids)->pluck('product_id')->unique();
        $count = Review::whereKey($ids)->update(['status' => $status->value]);
        $productIds->each(fn ($pid) => $this->recompute((int) $pid));

        return $count;
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        $productIds = Review::whereKey($ids)->pluck('product_id')->unique();
        $count = Review::whereKey($ids)->delete();
        $productIds->each(fn ($pid) => $this->recompute((int) $pid));

        return $count;
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
