<?php

declare(strict_types=1);

namespace App\Services\Frontend;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
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

        ['lines' => $normalized, 'activeIds' => $activeIds, 'validVariantIds' => $validVariantIds] = $this->resolve($clientLines);

        DB::transaction(function () use ($cart, $normalized, $activeIds, $validVariantIds): void {
            $cart->items()->delete();

            $normalized
                ->filter(fn (array $line): bool => in_array($line['product_id'], $activeIds, true))
                ->groupBy(fn (array $line): string => $line['product_id'].'|'.$line['variant_id'].'|'.$line['size'].'|'.$line['color'])
                ->each(function (Collection $group) use ($cart, $validVariantIds): void {
                    $first = $group->first();
                    $cart->items()->create([
                        'product_id' => $first['product_id'],
                        'product_variant_id' => in_array($first['variant_id'], $validVariantIds, true) ? $first['variant_id'] : null,
                        'size' => $first['size'] ?: null,
                        'color' => $first['color'] ?: null,
                        'quantity' => (int) $group->sum('quantity'),
                    ]);
                });
        });

        return $this->mapItems($cart->items()->get());
    }

    /**
     * Check a bag (guest or signed-in) without saving it: fill in each line's
     * exact variant id, and flag lines that can no longer be bought — product
     * gone/inactive, or an option (size + colour) the product doesn't sell.
     *
     * @param  array<int, array<string, mixed>>  $clientLines
     *                                                         `stock` is how many units of that exact option can be bought right now
     *                                                         (the variant's stock, or the product's for single products).
     * @return array<int, array{key: string, variant_id: int|null, available: bool, stock: int}>
     */
    public function check(array $clientLines): array
    {
        ['lines' => $lines, 'activeIds' => $activeIds, 'validVariantIds' => $validVariantIds, 'variantStock' => $variantStock] = $this->resolve($clientLines);

        $products = Product::query()
            ->whereIn('id', $activeIds)
            ->get(['id', 'product_type', 'stock'])
            ->keyBy('id');

        return $lines
            ->map(function (array $line) use ($products, $validVariantIds, $variantStock): array {
                $variantId = in_array($line['variant_id'], $validVariantIds, true) ? $line['variant_id'] : null;
                $product = $products->get($line['product_id']);
                $sellable = $product && ($variantId !== null || ! $product->isVariable());

                $stock = match (true) {
                    ! $sellable => 0,
                    $variantId !== null => (int) ($variantStock[$variantId] ?? 0),
                    default => max(0, (int) $product->stock),
                };

                return [
                    'key' => $line['key'],
                    'variant_id' => $variantId,
                    'available' => $sellable,
                    'stock' => max(0, $stock),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Normalize client lines and resolve each one's variant: a valid id is
     * kept, otherwise it is looked up from the size code + colour key.
     *
     * @param  array<int, array<string, mixed>>  $clientLines
     * @return array{lines: Collection<int, array<string, mixed>>, activeIds: array<int, int>, validVariantIds: array<int, int>, variantStock: array<int, int>}
     */
    private function resolve(array $clientLines): array
    {
        $normalized = collect($clientLines)
            ->map(fn (array $line): array => [
                'key' => (string) ($line['key'] ?? ''),
                'product_id' => (int) ($line['id'] ?? 0),
                'variant_id' => (int) ($line['variant_id'] ?? 0),
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

        // Active variants of these products, keyed "{product}#{size code}|{colour key}".
        $variants = ProductVariant::query()
            ->with(['size:id,code', 'color:id,name,code'])
            ->whereIn('product_id', $activeIds)
            ->where('status', true)
            ->get();

        $validVariantIds = $variants->pluck('id')->all();
        $variantKeys = $variants->mapWithKeys(fn (ProductVariant $variant): array => [
            $variant->product_id.'#'.$this->products->variantKey($variant) => $variant->id,
        ]);
        $variantProduct = $variants->pluck('product_id', 'id');
        $variantStock = $variants->pluck('stock', 'id')->map(fn ($stock): int => max(0, (int) $stock))->all();

        $lines = $normalized->map(function (array $line) use ($variantKeys, $variantProduct): array {
            // Keep a sent id only if it is an active variant of this very product.
            if (($variantProduct[$line['variant_id']] ?? null) !== $line['product_id']) {
                $key = $line['product_id'].'#'.mb_strtolower($line['size']).'|'.mb_strtolower($line['color']);
                $line['variant_id'] = (int) ($variantKeys[$key] ?? 0);
            }

            return $line;
        });

        return ['lines' => $lines, 'activeIds' => $activeIds, 'validVariantIds' => $validVariantIds, 'variantStock' => $variantStock];
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
                $size = $item->size ?: $mapped['quick_add']['size'];
                $color = $item->color ?: $mapped['quick_add']['color'];

                return [
                    'id' => $product->id,
                    'variant_id' => $item->product_variant_id,
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
