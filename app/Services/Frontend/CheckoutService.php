<?php

declare(strict_types=1);

namespace App\Services\Frontend;

use App\Enums\StockMovementType;
use App\Exceptions\CheckoutException;
use App\Helpers\ImageManager;
use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\TaxRule;
use App\Services\Admin\SettingService;
use App\Services\Admin\StockService;
use App\Services\Admin\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Supplies the storefront checkout with admin-managed shipping methods, payment
 * methods and tax so delivery options + totals reflect real configuration.
 */
final class CheckoutService
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly StockService $stock,
        private readonly WalletService $wallet,
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
     * The lowest "free over" threshold across active shipping methods, or null
     * when no method offers free shipping. Drives the cart free-shipping nudge.
     */
    public function freeShippingThreshold(): ?float
    {
        $min = ShippingMethod::query()
            ->where('status', true)
            ->whereNotNull('free_over_amount')
            ->where('free_over_amount', '>', 0)
            ->min('free_over_amount');

        return $min !== null ? (float) $min : null;
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
     * True when the given payment-method code is an online (gateway) method.
     */
    public function isOnlineMethod(?string $code): bool
    {
        return $this->methodType($code) === 'online';
    }

    /**
     * True when the given payment-method code is the in-house wallet.
     */
    public function isWalletMethod(?string $code): bool
    {
        return $this->methodType($code) === 'wallet';
    }

    private function methodType(?string $code): ?string
    {
        if (blank($code)) {
            return null;
        }

        return collect($this->paymentMethods())->firstWhere('code', $code)['type'] ?? null;
    }

    /**
     * Validate a coupon code against a subtotal (for the checkout AJAX check).
     *
     * @return array{valid: bool, code: string, discount: float, message: string}
     */
    public function validateCoupon(string $code, float $subtotal): array
    {
        $coupon = Coupon::active()->code($code)->first();
        $label = $coupon?->code ?? mb_strtoupper(trim($code));

        if (! $coupon) {
            return ['valid' => false, 'code' => $label, 'discount' => 0, 'message' => 'That code is invalid or expired.'];
        }

        if ($coupon->min_spend !== null && $subtotal < (float) $coupon->min_spend) {
            return ['valid' => false, 'code' => $label, 'discount' => 0, 'message' => 'Spend at least $'.number_format((float) $coupon->min_spend, 2).' to use this code.'];
        }

        if ($coupon->reachedPerUserLimit(Auth::id())) {
            return ['valid' => false, 'code' => $label, 'discount' => 0, 'message' => 'You have already used this code the maximum number of times.'];
        }

        $discount = $coupon->discountFor($subtotal);

        if ($discount <= 0) {
            return ['valid' => false, 'code' => $label, 'discount' => 0, 'message' => 'This code does not apply to your bag.'];
        }

        return ['valid' => true, 'code' => $label, 'discount' => $discount, 'message' => 'Coupon applied — you saved $'.number_format($discount, 2).'.'];
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
            throw new CheckoutException('Your cart is empty.');
        }

        $products = Product::query()
            ->with('variants.values')
            ->where('status', 'active')
            ->whereKey($items->pluck('id')->map(fn ($v) => (int) $v)->unique()->all())
            ->get()
            ->keyBy('id');

        $order = DB::transaction(function () use ($items, $products, $data) {
            $lines = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                $product = $products->get((int) $item['id']);
                if (! $product) {
                    continue; // unavailable / removed product — skip
                }

                $qty = max(1, (int) $item['qty']);
                $variant = $this->matchVariant(
                    $product,
                    $item['size'] ?? null,
                    $item['color'] ?? null,
                    isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                );

                // Row-lock the stockable being sold so two concurrent checkouts
                // can't both pass the check and oversell the same stock.
                $stockable = $variant ?: ($product->product_type->value === 'single' ? $product : null);
                if ($stockable) {
                    $stockable = $stockable->newQuery()->lockForUpdate()->find($stockable->getKey());
                }
                if ($stockable && (int) $stockable->stock < $qty) {
                    throw new CheckoutException(sprintf(
                        '"%s" only has %d left in stock. Please adjust the quantity.',
                        $product->name,
                        max(0, (int) $stockable->stock),
                    ));
                }

                $price = $variant && $variant->price !== null ? (float) $variant->price : (float) $product->final_price;
                $lineTotal = round($price * $qty, 2);
                $subtotal += $lineTotal;

                $lines[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'stockable' => $stockable,
                    'qty' => $qty,
                    'price' => $price,
                    'line_total' => $lineTotal,
                    'label' => trim(($item['size'] ?? '').($item['color'] ? ' / '.$item['color'] : ''), ' /'),
                ];
            }

            if (empty($lines)) {
                throw new CheckoutException('None of the cart items are available.');
            }

            // Coupon (re-validated server-side — client discount is never trusted).
            $coupon = filled($data['coupon'] ?? null)
                ? Coupon::active()->code((string) $data['coupon'])->first()
                : null;

            // Enforce the per-customer redemption cap (global cap is in the scope).
            if ($coupon && $coupon->reachedPerUserLimit(Auth::id())) {
                throw new CheckoutException('You have already used this coupon the maximum number of times.');
            }

            $discount = $coupon ? $coupon->discountFor($subtotal) : 0.0;

            $method = ShippingMethod::query()->where('status', true)->find($data['shipping_id'] ?? null);
            $shipping = $method ? $method->costFor($subtotal) : 0.0;
            $tax = round(max(0, $subtotal - $discount) * $this->taxRate(), 2);
            $grand = round(max(0, $subtotal - $discount) + $shipping + $tax, 2);

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
                'discount_total' => $discount,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'shipping_total' => $shipping,
                'tax_total' => $tax,
                'grand_total' => $grand,
                'shipping_method' => $method?->name,
                'payment_method' => $data['payment'] ?? 'card',
                'payment_status' => 'unpaid',
                'placed_at' => now(),
            ]);

            // Count the redemption once the order exists.
            $coupon?->increment('used_count');

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

                // Decrement the row-locked stockable captured during the check.
                if ($stockable = $line['stockable']) {
                    $this->stock->adjust($stockable, -$line['qty'], StockMovementType::Sale, 'Order '.$order->order_number);
                }
            }

            // Pay from the store wallet: block unless it covers the whole order,
            // then debit the balance and mark the order paid immediately.
            if ($this->isWalletMethod($data['payment'] ?? null)) {
                $user = Auth::user();

                if (! $user) {
                    throw new CheckoutException('Please sign in to pay with your wallet.');
                }

                if (! $this->wallet->hasSufficient($user, (float) $grand)) {
                    throw new CheckoutException('Your wallet balance is not enough for this order. Please top up or choose another payment method.');
                }

                $this->wallet->debit($user, (float) $grand, 'payment', 'Order '.$order->order_number, $order->id);
                $order->forceFill(['payment_status' => 'paid', 'paid_at' => now()])->save();
            }

            // Empty the customer's saved cart now that the order is placed.
            if ($userId = Auth::id()) {
                Cart::where('user_id', $userId)->first()?->items()->delete();
            }

            return $order;
        });

        // Send the confirmation + invoice once the order is safely committed.
        // Queued (ShouldQueue), so a mail failure never blocks the checkout.
        // Honours the admin "order confirmation email" toggle in Settings.
        if (filled($order->customer_email) && $this->settings->orderEmailEnabled()) {
            Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));
        }

        // Optional: copy the order (with invoice) to the admin alert address.
        if ($adminEmail = $this->settings->adminOrderAlertEmail()) {
            Mail::to($adminEmail)->send(new OrderConfirmationMail($order));
        }

        return $order;
    }

    /**
     * Resolve the variant being sold. Prefers an explicit variant id from the
     * cart (exact, authoritative) and only falls back to best-effort matching on
     * the size + colour labels for legacy/guest carts that don't carry the id.
     */
    private function matchVariant(Product $product, ?string $size, ?string $color, ?int $variantId = null): ?ProductVariant
    {
        if ($product->variants->isEmpty()) {
            return null;
        }

        if ($variantId) {
            $exact = $product->variants->firstWhere('id', $variantId);
            if ($exact) {
                return $exact;
            }
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
