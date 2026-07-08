<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Real variants (with their product) to snapshot into order lines.
        $variants = ProductVariant::with(['product:id,name,thumbnail,price', 'values'])->get();
        $customers = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))->pluck('id');

        Order::factory()->count(25)->create()->each(function (Order $order) use ($variants, $customers): void {
            // ~60% attach to a known customer, else guest.
            if ($customers->isNotEmpty() && fake()->boolean(60)) {
                $order->user_id = $customers->random();
            }

            $lineCount = fake()->numberBetween(1, 4);

            for ($i = 0; $i < $lineCount; $i++) {
                if ($variants->isNotEmpty()) {
                    $variant = $variants->random();
                    $price = (float) ($variant->price ?? $variant->product?->price ?? fake()->randomFloat(2, 15, 80));
                    $quantity = fake()->numberBetween(1, 3);

                    $order->details()->create([
                        'product_id' => $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'name' => $variant->product?->name ?? 'Product',
                        'variant_label' => $variant->variant_label,
                        'sku' => $variant->sku,
                        'image' => $variant->image ?? $variant->product?->thumbnail,
                        'price' => $price,
                        'quantity' => $quantity,
                        'line_total' => round($price * $quantity, 2),
                    ]);
                } else {
                    $order->details()->save(\App\Models\OrderDetail::factory()->make());
                }
            }

            $this->recalcTotals($order);
        });
    }

    private function recalcTotals(Order $order): void
    {
        $subtotal = (float) $order->details()->sum('line_total');
        $shipping = $subtotal >= 75 ? 0.0 : 6.95;
        $tax = round($subtotal * 0.08, 2);

        $order->update([
            'subtotal' => $subtotal,
            'shipping_total' => $shipping,
            'tax_total' => $tax,
            'grand_total' => round($subtotal + $shipping + $tax, 2),
        ]);
    }
}
