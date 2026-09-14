<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Admin\LoyaltyService;
use App\Services\Admin\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

function enableLoyalty(float $rate = 10.0): void
{
    Setting::set('loyalty_enabled', '1', 'loyalty');
    Setting::set('loyalty_earn_rate', (string) $rate, 'loyalty');
}

/**
 * @return array{0: Order, 1: Product}
 */
function loyaltyOrder(?User $customer, string $paymentStatus = 'unpaid'): array
{
    $category = Category::firstOrCreate(['slug' => 'loyalty-tees'], ['name' => 'Loyalty Tees']);
    $product = Product::factory()->create([
        'category_id' => $category->id, 'status' => 'active', 'product_type' => 'single', 'stock' => 10,
    ]);

    $order = Order::create([
        'user_id' => $customer?->id, 'status' => 'pending', 'fulfillment_status' => 'unfulfilled',
        'payment_status' => $paymentStatus, 'customer_name' => 'A B', 'customer_email' => 'a@b.com',
        'shipping_address' => 'x', 'subtotal' => 100, 'discount_total' => 0, 'shipping_total' => 0,
        'tax_total' => 0, 'grand_total' => 100, 'placed_at' => now(),
    ]);
    $order->details()->create([
        'product_id' => $product->id, 'name' => $product->name, 'sku' => $product->sku,
        'price' => 100, 'quantity' => 1, 'line_total' => 100,
    ]);

    return [$order, $product];
}

it('credits wallet points when a signed-in customer order is marked paid', function () {
    enableLoyalty(rate: 10.0);
    $customer = User::factory()->create(['wallet_balance' => 0]);
    [$order] = loyaltyOrder($customer);

    app(LoyaltyService::class)->awardForOrder($order->fresh());

    expect((float) $customer->fresh()->wallet_balance)->toBe(10.0)
        ->and($order->fresh()->loyalty_credited_at)->not->toBeNull()
        ->and(WalletTransaction::where('type', 'loyalty')->where('order_id', $order->id)->exists())->toBeTrue();
});

it('never double-credits the same order', function () {
    enableLoyalty(rate: 10.0);
    $customer = User::factory()->create(['wallet_balance' => 0]);
    [$order] = loyaltyOrder($customer);

    $service = app(LoyaltyService::class);
    $service->awardForOrder($order->fresh());
    $service->awardForOrder($order->fresh());

    expect((float) $customer->fresh()->wallet_balance)->toBe(10.0)
        ->and(WalletTransaction::where('type', 'loyalty')->count())->toBe(1);
});

it('does not credit anything while loyalty is disabled (the default)', function () {
    $customer = User::factory()->create(['wallet_balance' => 0]);
    [$order] = loyaltyOrder($customer);

    app(LoyaltyService::class)->awardForOrder($order->fresh());

    expect((float) $customer->fresh()->wallet_balance)->toBe(0.0);
});

it('skips guest orders that have no wallet to credit', function () {
    enableLoyalty(rate: 10.0);
    [$order] = loyaltyOrder(null);

    app(LoyaltyService::class)->awardForOrder($order->fresh());

    expect(WalletTransaction::where('type', 'loyalty')->exists())->toBeFalse();
});

it('awards points automatically when an admin marks an order paid', function () {
    enableLoyalty(rate: 20.0);
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $customer = User::factory()->create(['wallet_balance' => 0]);
    [$order] = loyaltyOrder($customer, paymentStatus: 'unpaid');

    app(OrderService::class)->updateFulfilment($order, [
        'status' => 'processing',
        'fulfillment_status' => 'unfulfilled',
        'payment_status' => 'paid',
    ]);

    expect((float) $customer->fresh()->wallet_balance)->toBe(20.0);
});
