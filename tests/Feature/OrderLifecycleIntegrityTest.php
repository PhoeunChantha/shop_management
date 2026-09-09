<?php

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Admin\OrderService;
use App\Services\Admin\ReturnRequestService;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

/**
 * @return array{0: Order, 1: Product}
 */
function lifecycleOrder(string $status, int $productStock = 5, int $orderedQty = 2): array
{
    $category = Category::firstOrCreate(['slug' => 'lifecycle-tees'], ['name' => 'Lifecycle Tees']);
    $product = Product::factory()->create([
        'category_id' => $category->id, 'status' => 'active', 'product_type' => 'single',
        'stock' => $productStock, 'price' => 50, 'discount_type' => null, 'discount_amount' => 0,
    ]);

    $order = Order::create([
        'user_id' => null, 'status' => $status, 'fulfillment_status' => 'fulfilled', 'payment_status' => 'paid',
        'customer_name' => 'A B', 'customer_email' => 'a@b.com', 'shipping_address' => 'x', 'subtotal' => 100, 'discount_total' => 0,
        'shipping_total' => 0, 'tax_total' => 0, 'grand_total' => 100, 'placed_at' => now(), 'paid_at' => now(),
    ]);
    $order->details()->create([
        'product_id' => $product->id, 'name' => $product->name, 'sku' => $product->sku,
        'price' => 50, 'quantity' => $orderedQty, 'line_total' => 50 * $orderedQty,
    ]);

    return [$order, $product];
}

// -- Order status transition validation ------------------------------------------

it('rejects an order status update via a route that skips a state in the fulfilment flow', function () {
    [$order] = lifecycleOrder('delivered');

    $this->patch(route('admin.orders.update', $order->id), [
        'status' => 'pending',
        'fulfillment_status' => 'unfulfilled',
    ])->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered);
});

it('rejects reviving a cancelled order', function () {
    [$order] = lifecycleOrder('cancelled');

    $this->patch(route('admin.orders.update', $order->id), [
        'status' => 'paid',
        'fulfillment_status' => 'unfulfilled',
    ])->assertSessionHasErrors('status');
});

it('allows a valid forward transition', function () {
    [$order] = lifecycleOrder('paid');

    $this->patch(route('admin.orders.update', $order->id), [
        'status' => 'processing',
        'fulfillment_status' => 'unfulfilled',
    ])->assertSessionHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Processing);
});

// -- Stock restoration on cancel/refund -------------------------------------------

it('restores stock when an order is cancelled', function () {
    [$order, $product] = lifecycleOrder('paid', productStock: 3, orderedQty: 2);

    app(OrderService::class)->updateFulfilment($order, [
        'status' => 'cancelled',
        'fulfillment_status' => 'unfulfilled',
    ]);

    expect($product->fresh()->stock)->toBe(5)
        ->and(StockMovement::where('product_id', $product->id)->where('type', 'return')->exists())->toBeTrue();
});

it('restores stock when an order is refunded', function () {
    [$order, $product] = lifecycleOrder('delivered', productStock: 0, orderedQty: 4);

    app(OrderService::class)->updateFulfilment($order, [
        'status' => 'refunded',
        'fulfillment_status' => 'fulfilled',
        'payment_status' => 'refunded',
    ]);

    expect($product->fresh()->stock)->toBe(4);
});

it('does not restock a normal forward transition', function () {
    [$order, $product] = lifecycleOrder('paid', productStock: 3, orderedQty: 2);

    app(OrderService::class)->updateFulfilment($order, [
        'status' => 'processing',
        'fulfillment_status' => 'unfulfilled',
    ]);

    expect($product->fresh()->stock)->toBe(3);
});

// -- Stock restoration on return received ------------------------------------------

it('restores stock when a return is marked received', function () {
    [$order, $product] = lifecycleOrder('delivered', productStock: 0, orderedQty: 3);
    $detail = $order->details->first();

    $return = app(ReturnRequestService::class)->create([
        'order_id' => $order->id,
        'reason' => 'defective',
        'items' => [
            ['order_detail_id' => $detail->id, 'return' => true, 'quantity' => 2],
        ],
    ]);

    expect($product->fresh()->stock)->toBe(0); // requesting a return alone must not restock

    app(ReturnRequestService::class)->update($return, [
        'status' => 'received',
        'refund_status' => 'not_refunded',
    ]);

    expect($product->fresh()->stock)->toBe(2);
});
