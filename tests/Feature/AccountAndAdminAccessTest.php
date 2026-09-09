<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Admin\ProductService;
use App\Services\Frontend\AccountService;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');

    $this->category = Category::create(['name' => 'Tees', 'slug' => 'tees']);
});

// -- Cross-account order isolation --------------------------------------------------

it('never surfaces another account\'s order via a matching checkout email', function () {
    $owner = User::factory()->create(['email' => 'owner@example.com']);

    // The customer checked out while logged in but typed the owner's email —
    // this order legitimately belongs to the customer's account (user_id set).
    $order = Order::create([
        'user_id' => $this->customer->id, 'status' => 'paid', 'fulfillment_status' => 'unfulfilled',
        'payment_status' => 'paid', 'customer_name' => 'X', 'customer_email' => $owner->email, 'shipping_address' => 'x',
        'subtotal' => 10, 'discount_total' => 0, 'shipping_total' => 0, 'tax_total' => 0,
        'grand_total' => 10, 'placed_at' => now(),
    ]);

    $this->actingAs($owner);
    $found = app(AccountService::class)->findOrder((string) $order->id);

    expect($found)->toBeNull();
});

it('lets a customer claim a true guest order placed with their email', function () {
    $user = User::factory()->create(['email' => 'claimant@example.com']);

    $guestOrder = Order::create([
        'user_id' => null, 'status' => 'paid', 'fulfillment_status' => 'unfulfilled',
        'payment_status' => 'paid', 'customer_name' => 'X', 'customer_email' => $user->email, 'shipping_address' => 'x',
        'subtotal' => 10, 'discount_total' => 0, 'shipping_total' => 0, 'tax_total' => 0,
        'grand_total' => 10, 'placed_at' => now(),
    ]);

    $this->actingAs($user);
    $found = app(AccountService::class)->findOrder((string) $guestOrder->id);

    expect($found)->not->toBeNull()
        ->and($found['id'])->toBe($guestOrder->id);
});

// -- Dashboard authorization ---------------------------------------------------------

it('denies the admin dashboard to a user without view dashboard permission', function () {
    $this->actingAs($this->customer)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('allows the admin dashboard for an admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

// -- Product delete guard against purchase-order history -----------------------------

it('blocks deleting a product referenced by a purchase order', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);
    $supplier = Supplier::create(['name' => 'Acme Supply', 'status' => true]);
    $po = PurchaseOrder::create(['po_number' => 'PO-TEST-1', 'supplier_id' => $supplier->id, 'status' => 'draft', 'subtotal' => 50]);
    PurchaseOrderItem::create([
        'purchase_order_id' => $po->id, 'product_id' => $product->id, 'name' => $product->name,
        'sku' => $product->sku, 'quantity_ordered' => 5, 'quantity_received' => 0, 'unit_cost' => 10, 'line_total' => 50,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.products.destroy', $product->id))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

it('bulk delete skips a purchase-order-referenced product and still deletes the rest', function () {
    $blocked = Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Blocked Product']);
    $free = Product::factory()->create(['category_id' => $this->category->id]);

    $supplier = Supplier::create(['name' => 'Acme Supply', 'status' => true]);
    $po = PurchaseOrder::create(['po_number' => 'PO-TEST-2', 'supplier_id' => $supplier->id, 'status' => 'draft', 'subtotal' => 50]);
    PurchaseOrderItem::create([
        'purchase_order_id' => $po->id, 'product_id' => $blocked->id, 'name' => $blocked->name,
        'sku' => $blocked->sku, 'quantity_ordered' => 5, 'quantity_received' => 0, 'unit_cost' => 10, 'line_total' => 50,
    ]);

    $result = app(ProductService::class)->bulkDelete([$blocked->id, $free->id]);

    expect($result['deleted'])->toBe(1)
        ->and($result['blocked'])->toContain('Blocked Product');
    $this->assertDatabaseHas('products', ['id' => $blocked->id]);
    $this->assertDatabaseMissing('products', ['id' => $free->id]);
});
