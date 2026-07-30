<?php

declare(strict_types=1);

namespace App\Services\Frontend;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Tracks recently-viewed products: session for guests, the `recently_viewed`
 * table for signed-in customers (so it follows them across devices).
 */
final class RecentlyViewedService
{
    private const SESSION_KEY = 'recently_viewed';

    private const LIMIT = 12;

    public function __construct(
        private readonly ProductService $products,
    ) {}

    /**
     * Record that the current visitor viewed a product.
     */
    public function record(int $productId): void
    {
        if ($user = Auth::user()) {
            DB::table('recently_viewed')->updateOrInsert(
                ['user_id' => $user->id, 'product_id' => $productId],
                ['viewed_at' => now(), 'updated_at' => now(), 'created_at' => now()],
            );

            return;
        }

        $ids = array_values(array_filter(
            (array) Session::get(self::SESSION_KEY, []),
            fn ($id): bool => (int) $id !== $productId,
        ));

        array_unshift($ids, $productId);
        Session::put(self::SESSION_KEY, array_slice($ids, 0, self::LIMIT));
    }

    /**
     * Recently-viewed products (most recent first), mapped for cards.
     *
     * @return array<int, array<string, mixed>>
     */
    public function products(int $limit = 8, ?int $excludeId = null): array
    {
        $ids = $this->recentIds();

        if ($excludeId !== null) {
            $ids = array_values(array_filter($ids, fn (int $id): bool => $id !== $excludeId));
        }

        $ids = array_slice($ids, 0, $limit);

        if ($ids === []) {
            return [];
        }

        // Preserve the recency order (whereIn does not guarantee it).
        $order = array_flip($ids);

        return $this->products->mappedActiveProducts()
            ->whereIn('id', $ids)
            ->sortBy(fn (array $product): int => $order[$product['id']] ?? PHP_INT_MAX)
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function recentIds(): array
    {
        if ($user = Auth::user()) {
            return DB::table('recently_viewed')
                ->where('user_id', $user->id)
                ->orderByDesc('viewed_at')
                ->limit(self::LIMIT)
                ->pluck('product_id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        return array_map('intval', (array) Session::get(self::SESSION_KEY, []));
    }
}
