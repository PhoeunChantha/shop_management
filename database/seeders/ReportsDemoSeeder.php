<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\ReturnRequest;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Populates realistic demo data across the last 30 days so every admin report
 * (sales, products, stock, payments, customers, purchasing, registrations,
 * returns) shows meaningful figures. Safe to run repeatedly — it only appends.
 */
class ReportsDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $customers = $this->seedCustomers();
            $products = $this->seedProducts();
            $orders = $this->seedOrders($customers, $products);
            $this->seedSuppliersAndPurchaseOrders();
            $this->seedReturns($orders);
        });

        $this->command?->info('Reports demo data seeded for the last 30 days.');
    }

    /**
     * Registered customers with signup dates spread across the range.
     *
     * @return array<int, array{name: string, email: string, user_id: int}>
     */
    private function seedCustomers(): array
    {
        $role = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $pool = [];

        for ($i = 1; $i <= 14; $i++) {
            $createdAt = Carbon::now()->subDays(random_int(0, 29))->setTime(random_int(8, 20), random_int(0, 59));
            $email = 'demo.customer'.$i.'@example.com';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => fake()->name(),
                    'password' => Hash::make('password'),
                    'email_verified_at' => random_int(1, 100) <= 70 ? $createdAt : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ],
            );

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            $pool[] = ['name' => $user->name, 'email' => $user->email, 'user_id' => $user->id];
        }

        return $pool;
    }

    /**
     * Demo catalog with a deliberate mix of healthy / low / out-of-stock items.
     *
     * @return \Illuminate\Support\Collection<int, Product>
     */
    private function seedProducts(): \Illuminate\Support\Collection
    {
        // stock, low_stock_alert pairs → out of stock, low, then healthy.
        $stockPlan = [[0, 5], [0, 5], [2, 5], [3, 5], [4, 5], [40, 5], [60, 5], [80, 5], [120, 5], [150, 5]];

        return collect($stockPlan)->map(function (array $plan, int $index): Product {
            [$stock, $alert] = $plan;

            return Product::factory()->create([
                'product_type' => 'single',
                'sku' => 'DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'stock' => $stock,
                'low_stock_alert' => $alert,
                'status' => 'active',
            ]);
        });
    }

    /**
     * Orders (paid + unpaid + refunded) with line items across the range.
     *
     * @param  array<int, array{name: string, email: string, user_id: int}>  $customers
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @return \Illuminate\Support\Collection<int, Order>
     */
    private function seedOrders(array $customers, \Illuminate\Support\Collection $products): \Illuminate\Support\Collection
    {
        $orders = collect();

        for ($i = 0; $i < 45; $i++) {
            $placedAt = Carbon::now()->subDays(random_int(0, 29))->setTime(random_int(8, 21), random_int(0, 59));

            // 78% realised revenue, rest unpaid / refunded for payment-mix variety.
            $roll = random_int(1, 100);
            $paymentStatus = match (true) {
                $roll <= 70 => PaymentStatus::Paid,
                $roll <= 78 => PaymentStatus::PartiallyRefunded,
                $roll <= 84 => PaymentStatus::Refunded,
                default => PaymentStatus::Unpaid,
            };

            $isPaid = $paymentStatus !== PaymentStatus::Unpaid;
            $status = match ($paymentStatus) {
                PaymentStatus::Unpaid => OrderStatus::Pending,
                PaymentStatus::Refunded => OrderStatus::Refunded,
                default => fake()->randomElement([OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered]),
            };

            // 65% registered customer, else guest.
            if (random_int(1, 100) <= 65) {
                $customer = fake()->randomElement($customers);
                $userId = $customer['user_id'];
                $name = $customer['name'];
                $email = $customer['email'];
            } else {
                $userId = null;
                $name = fake()->name();
                $email = fake()->safeEmail();
            }

            $lines = $products->random(random_int(1, 3));
            $subtotal = 0.0;

            $order = Order::create([
                'user_id' => $userId,
                'status' => $status->value,
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => fake()->optional()->numerify('0## ### ####'),
                'shipping_address' => fake()->streetAddress(),
                'shipping_city' => fake()->city(),
                'shipping_zip' => fake()->postcode(),
                'shipping_country' => 'US',
                'subtotal' => 0,
                'discount_total' => 0,
                'shipping_total' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'shipping_method' => fake()->randomElement(['standard', 'express', 'pickup']),
                'payment_method' => fake()->randomElement(['card', 'cod']),
                'payment_status' => $paymentStatus->value,
                'paid_at' => $isPaid ? $placedAt : null,
                'placed_at' => $placedAt,
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);

            foreach ($lines as $product) {
                $qty = random_int(1, 4);
                $price = (float) $product->price;
                $lineTotal = round($price * $qty, 2);
                $subtotal += $lineTotal;

                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $price,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                ]);
            }

            $discount = random_int(1, 100) <= 25 ? round($subtotal * 0.1, 2) : 0.0;
            $shipping = (float) fake()->randomElement([0, 5, 7.5, 10]);
            $tax = round(($subtotal - $discount) * 0.08, 2);

            $order->update([
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'shipping_total' => $shipping,
                'tax_total' => $tax,
                'grand_total' => round($subtotal - $discount + $shipping + $tax, 2),
            ]);

            $orders->push($order);
        }

        return $orders;
    }

    private function seedSuppliersAndPurchaseOrders(): void
    {
        $suppliers = collect(['Northwind Textiles', 'Apex Apparel Co', 'Metro Fabric Supply'])
            ->map(fn (string $name): Supplier => Supplier::firstOrCreate(
                ['name' => $name],
                ['contact_name' => fake()->name(), 'email' => fake()->companyEmail(), 'phone' => fake()->numerify('0## ### ####'), 'status' => true],
            ));

        for ($i = 0; $i < 12; $i++) {
            $orderedAt = Carbon::now()->subDays(random_int(0, 29));
            $status = fake()->randomElement(['ordered', 'ordered', 'partial', 'received', 'received']);

            PurchaseOrder::create([
                'po_number' => 'PO-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
                'supplier_id' => $suppliers->random()->id,
                'status' => $status,
                'ordered_at' => $orderedAt,
                'expected_at' => $orderedAt->copy()->addDays(random_int(3, 14)),
                'received_at' => $status === 'received' ? $orderedAt->copy()->addDays(random_int(2, 10)) : null,
                'subtotal' => fake()->randomFloat(2, 200, 4000),
                'created_at' => $orderedAt,
                'updated_at' => $orderedAt,
            ]);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     */
    private function seedReturns(\Illuminate\Support\Collection $orders): void
    {
        $paidOrders = $orders->filter(
            fn (Order $order): bool => in_array($order->payment_status->value, [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value, PaymentStatus::Refunded->value], true)
        );

        if ($paidOrders->isEmpty()) {
            return;
        }

        foreach ($paidOrders->random(min(8, $paidOrders->count())) as $order) {
            $requestedAt = Carbon::parse($order->placed_at)->addDays(random_int(1, 6));
            if ($requestedAt->isFuture()) {
                $requestedAt = Carbon::now();
            }

            $status = fake()->randomElement(['requested', 'approved', 'received', 'refunded', 'refunded']);
            $refundStatus = match ($status) {
                'refunded' => 'refunded',
                'received' => fake()->randomElement(['pending', 'partial']),
                default => 'not_refunded',
            };
            $refund = in_array($refundStatus, ['partial', 'refunded'], true)
                ? round((float) $order->grand_total * ($refundStatus === 'partial' ? 0.5 : 1.0), 2)
                : 0.0;

            ReturnRequest::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'status' => $status,
                'refund_status' => $refundStatus,
                'reason' => fake()->randomElement(array_keys(ReturnRequest::REASONS)),
                'requested_amount' => (float) $order->grand_total,
                'refund_amount' => $refund,
                'requested_at' => $requestedAt,
                'approved_at' => in_array($status, ['approved', 'received', 'refunded'], true) ? $requestedAt->copy()->addDay() : null,
                'received_at' => in_array($status, ['received', 'refunded'], true) ? $requestedAt->copy()->addDays(2) : null,
                'refunded_at' => $status === 'refunded' ? $requestedAt->copy()->addDays(3) : null,
                'created_at' => $requestedAt,
                'updated_at' => $requestedAt,
            ]);
        }
    }
}
