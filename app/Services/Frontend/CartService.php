<?php

declare(strict_types=1);

namespace App\Services\Frontend;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Server-side (cross-device) cart persistence for signed-in customers.
 * Prices/images are always re-derived from the product — never trusted
 * from the client. Guests keep using localStorage only.
 */
final class CartService
{
    public function __construct(
        private readonly ProductService $products,
    ) {}

    /**
     * The customer's saved cart as client-shaped lines (id/name/price/…).
     *
     * @return array<int, array<string, mixed>>
     */
    public function linesFor(User $user): array
    {
        return $user->cart
            ? $this->mapItems($user->cart->items()->get())
            : [];
    }

    /**
     * Replace the saved cart with the given client lines (valid, active
     * products only) and return the reconciled cart.
     *
     * @param  array<int, array<string, mixed>>  $clientLines
     * @return array<int, array<string, mixed>>
     */
    public function sync(User $user, array $clientLines): array
    {
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $normalized = collect($clientLines)
            ->map(fn (array $line): array => [
                'product_id' => (int) ($line['id'] ?? 0),
                'size' => trim((string) ($line['size'] ?? '')),
                'color' => trim((string) ($line['color'] ?? '')),
                'quantity' => max(1, (int) ($line['qty'] ?? 1)),
            ])
            ->filter(fn (array $line): bool => $line['product_id'] > 0);

        $activeIds = Product::query()
            ->whereIn('id', $normalized->pluck('product_id')->unique()->all())
            ->where('status', 'active')
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($cart, $normalized, $activeIds): void {
            $cart->items()->delete();

            $normalized
                ->filter(fn (array $line): bool => in_array($line['product_id'], $activeIds, true))
                ->groupBy(fn (array $line): string => $line['product_id'].'|'.$line['size'].'|'.$line['color'])
                ->each(function (Collection $group) use ($cart): void {
                    $first = $group->first();
                    $cart->items()->create([
                        'product_id' => $first['product_id'],
                        'size' => $first['size'] ?: null,
                        'color' => $first['color'] ?: null,
                        'quantity' => (int) $group->sum('quantity'),
                    ]);
                });
        });

        return $this->mapItems($cart->items()->get());
    }

    public function clear(User $user): void
    {
        $user->cart?->items()->delete();
    }

    /**
     * @param  Collection<int, CartItem>  $items
     * @return array<int, array<string, mixed>>
     */
    private function mapItems(Collection $items): array
    {
        if ($items->isEmpty()) {
            return [];
        }

        $products = Product::query()
            ->with($this->products->relations())
            ->withSum('variants', 'stock')
            ->whereIn('id', $items->pluck('product_id')->unique()->all())
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        return $items
            ->map(function (CartItem $item) use ($products): ?array {
                $product = $products->get($item->product_id);

                if (! $product) {
                    return null;
                }

                $mapped = $this->products->map($product);
                $size = $item->size ?: 'M';
                $color = $item->color ?: ($mapped['colors'][0] ?? 'black');

                return [
                    'id' => $product->id,
                    'name' => $mapped['name'],
                    'price' => (float) $mapped['price'],
                    'tint' => $mapped['tint'],
                    'image' => $mapped['image_url'] ?? '',
                    'size' => $size,
                    'color' => $color,
                    'qty' => $item->quantity,
                    'key' => $product->id.'-'.$size.'-'.$color,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
