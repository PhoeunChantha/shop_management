<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StockMovementType;
use App\Helpers\ImageManager;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\TaxRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Supplies the storefront checkout with admin-managed shipping methods, payment
 * methods and tax so delivery options + totals reflect real configuration.
 */
final class FrontendCheckoutService
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly StockService $stock,
    ) {}

    /**
     * Active shipping methods mapped for the checkout (+ client totals JS).
     *
     * @return array<int, array<string, mixed>>
     */
    public function shippingMethods(): array
    {
        return ShippingMethod::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ShippingMethod $m): array => [
                'id' => $m->id,
                'name' => $m->name,
                'description' => $m->delivery_time ?: ($m->description ?: ''),
                'type' => $m->type->value,
                'rate' => (float) $m->rate,
                'free_over' => $m->free_over_amount !== null ? (float) $m->free_over_amount : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Active payment methods from Settings (online + manual), sorted.
     *
     * @return array<int, array<string, mixed>>
     */
    public function paymentMethods(): array
    {
        return collect($this->settings->paymentMethods())
            ->filter(fn (array $p): bool => (bool) ($p['status'] ?? false))
            ->sortBy('sort_order')
            ->map(fn (array $p): array => [
                'code' => $p['code'] ?? $p['id'] ?? 'card',
                'name' => $p['name'] ?? 'Card',
                'type' => $p['type'] ?? 'online',
                'description' => $p['description'] ?? '',
                'instructions' => $p['instructions'] ?? '',
                'image' => ! empty($p['image']) ? ImageManager::url($p['image'], 'settings') : null,
                'qr_image' => ! empty($p['qr_image']) ? ImageManager::url($p['qr_image'], 'settings') : null,
                'bank_name' => $p['bank_name'] ?? '',
                'account_name' => $p['account_name'] ?? '',
                'account_number' => $p['account_number'] ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * Effective tax rate (fraction, e.g. 0.085) from the applicable active,
     * exclusive tax rule (lowest sort order). Inclusive rules are already in
     * the price, so they are not added at checkout.
     */
    public function taxRate(): float
    {
        $percent = (float) TaxRule::query()
            ->where('status', true)
            ->where('is_inclusive', false)
            ->orderBy('sort_order')
            ->value('rate');

        return round($percent / 100, 4);
    }

    /**
     * Create a real order from the submitted cart.
     *
     * Every line is RE-PRICED server-side from the database — client prices are
     * never trusted. Shipping + tax use the admin configuration; stock for the
     * matched stockable is decremented and logged.
     *
     * @param  array<string, mixed>  $data  customer + items + shipping_id + payment
     */
    public function placeOrder(array $data): Order
    {
        $items = collect($data['items'] ?? [])
            ->filter(fn ($i) => ! empty($i['id']) && (int) ($i['qty'] ?? 0) > 0);

        if ($items->isEmpty()) {
            throw new \RuntimeException('Your cart is empty.');
        }

        $products = Product::query()
            ->with('variants.values')
            ->where('status', 'active')
            ->whereKey($items->pluck('id')->map(fn ($v) => (int) $v)->unique()->all())
            ->get()
            ->keyBy('id');

        return DB::transaction(function () use ($items, $products, $data) {
            $lines = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                $product = $products->get((int) $item['id']);
                if (! $product) {
                    continue; // unavailable / removed product — skip
                }

                $qty = max(1, (int) $item['qty']);
                $variant = $this->matchVariant($product, $item['size'] ?? null, $item['color'] ?? null);
                $price = $variant && $variant->price !== null ? (float) $variant->price : (float) $product->final_price;
                $lineTotal = round($price * $qty, 2);
                $subtotal += $lineTotal;

                $lines[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'qty' => $qty,
                    'price' => $price,
                    'line_total' => $lineTotal,
                    'label' => trim(($item['size'] ?? '').($item['color'] ? ' / '.$item['color'] : ''), ' /'),
                ];
            }

            if (empty($lines)) {
                throw new \RuntimeException('None of the cart items are available.');
            }

            $method = ShippingMethod::query()->where('status', true)->find($data['shipping_id'] ?? null);
            $shipping = $method ? $method->costFor($subtotal) : 0.0;
            $tax = round($subtotal * $this->taxRate(), 2);
            $grand = round($subtotal + $shipping + $tax, 2);

            $customer = $data['customer'] ?? [];

            $order = Order::create([
                'user_id' => Auth::id(),
                'status' => 'pending',
                'customer_name' => trim(($customer['first_name'] ?? '').' '.($customer['last_name'] ?? '')),
                'customer_email' => $customer['email'] ?? null,
                'customer_phone' => $customer['phone'] ?? null,
                'shipping_address' => $customer['address'] ?? null,
                'shipping_city' => $customer['city'] ?? null,
                'shipping_zip' => $customer['zip'] ?? null,
                'shipping_country' => $customer['country'] ?? null,
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'shipping_total' => $shipping,
                'tax_total' => $tax,
                'grand_total' => $grand,
                'shipping_method' => $method?->name,
                'payment_method' => $data['payment'] ?? 'card',
                'payment_status' => 'unpaid',
                'placed_at' => now(),
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];
                /** @var ProductVariant|null $variant */
                $variant = $line['variant'];

                $order->details()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'name' => $product->name,
                    'variant_label' => $line['label'] ?: null,
                    'sku' => $variant?->sku ?: $product->sku,
                    'image' => $product->thumbnail,
                    'price' => $line['price'],
                    'quantity' => $line['qty'],
                    'line_total' => $line['line_total'],
                ]);

                // Decrement stock on the matched stockable (variant, or single product).
                $stockable = $variant ?: ($product->product_type->value === 'single' ? $product : null);
                if ($stockable) {
                    $this->stock->adjust($stockable, -$line['qty'], StockMovementType::Sale, 'Order '.$order->order_number);
                }
            }

            return $order;
        });
    }

    /**
     * Best-effort match of a variant from the cart's size + colour labels.
     */
    private function matchVariant(Product $product, ?string $size, ?string $color): ?ProductVariant
    {
        if ($product->variants->isEmpty()) {
            return null;
        }

        $size = $size ? mb_strtolower(trim($size)) : null;
        $color = $color ? mb_strtolower(trim($color)) : null;

        return $product->variants->first(function (ProductVariant $variant) use ($size, $color): bool {
            $values = $variant->relationLoaded('values')
                ? $variant->values->pluck('value')->map(fn ($v) => mb_strtolower((string) $v))
                : collect();

            $sizeOk = ! $size || $values->contains($size);
            $colorOk = ! $color || $values->contains(fn ($v) => $v === $color || str_contains($v, $color) || str_contains($color, $v));

            return $sizeOk && $colorOk;
        });
    }
}
