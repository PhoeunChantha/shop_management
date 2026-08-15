<?php

use App\Enums\CouponType;
use App\Exceptions\CheckoutException;
use App\Mail\AbandonedCartReminderMail;
use App\Models\AbandonedCart;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use App\Services\Admin\AbandonedCartService;
use App\Services\Frontend\CartService;
use App\Services\Frontend\CheckoutService;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);
});

function singleProduct(float $price = 100): Product
{
    return Product::factory()->create([
        'status' => 'active', 'product_type' => 'single', 'stock' => 10,
        'price' => $price, 'discount_type' => null, 'discount_amount' => 0,
    ]);
}

function couponPayload(int $productId, float $price, string $code): array
{
    return [
        'customer' => ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'address' => 'x', 'city' => 'y'],
        'items' => [['id' => $productId, 'name' => 'x', 'price' => $price, 'size' => 'M', 'color' => 'black', 'qty' => 1]],
        'shipping_id' => null,
        'payment' => 'card',
        'coupon' => $code,
    ];
}

/* ---------------- §1.5 per-customer coupon limit ---------------- */

it('blocks a coupon once the customer reaches the per-user limit', function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $this->actingAs($user);

    Coupon::create(['code' => 'ONCE', 'type' => CouponType::Percentage, 'value' => 10, 'status' => true, 'per_user_limit' => 1]);
    $product = singleProduct(100);

    app(CheckoutService::class)->placeOrder(couponPayload($product->id, 100, 'ONCE'));

    // Preview now reflects the exhausted per-user cap.
    expect(app(CheckoutService::class)->validateCoupon('ONCE', 100)['valid'])->toBeFalse();

    // A second placement with the same coupon is rejected.
    expect(fn () => app(CheckoutService::class)->placeOrder(couponPayload($product->id, 100, 'ONCE')))
        ->toThrow(CheckoutException::class);
});

it('still allows a coupon under the per-user limit', function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $this->actingAs(User::factory()->create());

    Coupon::create(['code' => 'TWICE', 'type' => CouponType::Percentage, 'value' => 10, 'status' => true, 'per_user_limit' => 2]);

    expect(app(CheckoutService::class)->validateCoupon('TWICE', 100)['valid'])->toBeTrue();
});

/* ---------------- §1.2 cart variant round-trip ---------------- */

it('persists and returns the chosen variant id on the saved cart', function () {
    $user = User::factory()->create();
    $size = Size::create(['name' => 'Large', 'code' => 'L', 'status' => true]);
    $color = Color::create(['name' => 'Black', 'code' => 'black', 'hex_code' => '#000000', 'status' => true]);
    $product = Product::factory()->create(['status' => 'active', 'product_type' => 'variable', 'price' => 100]);
    $variant = ProductVariant::create([
        'product_id' => $product->id, 'size_id' => $size->id, 'color_id' => $color->id,
        'sku' => 'SKU-L-BLK', 'stock' => 4, 'price' => 80, 'status' => true,
    ]);

    $lines = app(CartService::class)->sync($user, [
        ['id' => $product->id, 'variant_id' => $variant->id, 'size' => 'L', 'color' => 'black', 'qty' => 2],
    ]);

    expect($lines[0]['variant_id'])->toBe($variant->id);
    $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id, 'quantity' => 2]);
});

it('ignores a forged variant id that does not belong to the product', function () {
    $user = User::factory()->create();
    $product = singleProduct(30);

    $lines = app(CartService::class)->sync($user, [
        ['id' => $product->id, 'variant_id' => 999999, 'size' => 'M', 'color' => 'black', 'qty' => 1],
    ]);

    expect($lines[0]['variant_id'])->toBeNull();
    $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'product_variant_id' => null]);
});

/* ---------------- §3 abandoned-cart reminder ---------------- */

it('sends a one-time abandoned-cart reminder and is idempotent', function () {
    Mail::fake();

    $cart = AbandonedCart::create([
        'cart_token' => 'tok-1', 'customer_email' => 'guest@example.com', 'customer_name' => 'Guest',
        'status' => 'new', 'item_count' => 1, 'subtotal' => 50, 'last_activity_at' => now()->subHours(2),
    ]);

    $sent = app(AbandonedCartService::class)->sendReminders();

    expect($sent)->toBe(1);
    Mail::assertQueued(AbandonedCartReminderMail::class);

    $cart->refresh();
    expect($cart->reminder_sent_at)->not->toBeNull()
        ->and($cart->status)->toBe('contacted');

    // Second run must not re-send.
    expect(app(AbandonedCartService::class)->sendReminders())->toBe(0);
});

it('does not remind carts that are too fresh', function () {
    Mail::fake();

    AbandonedCart::create([
        'cart_token' => 'tok-2', 'customer_email' => 'fresh@example.com',
        'status' => 'new', 'item_count' => 1, 'subtotal' => 50, 'last_activity_at' => now()->subMinutes(5),
    ]);

    expect(app(AbandonedCartService::class)->sendReminders())->toBe(0);
    Mail::assertNothingQueued();
});

/* ---------------- §1.4 guest checkout ---------------- */

it('lets a guest place an order (user_id null) via the checkout endpoint', function () {
    Mail::fake();
    $product = singleProduct(40);
    $items = json_encode([['id' => $product->id, 'name' => 'x', 'price' => 40, 'size' => 'M', 'color' => 'black', 'qty' => 1]]);

    $this->post(route('frontend.checkout.store'), [
        'email' => 'guest@example.com', 'first_name' => 'Guest', 'last_name' => 'User',
        'address' => '1 Main St', 'city' => 'Town', 'items' => $items,
    ])->assertRedirect(route('frontend.checkout.confirmation'));

    $this->assertDatabaseHas('orders', ['customer_email' => 'guest@example.com', 'user_id' => null]);
});

it('lets a guest reach the checkout page', function () {
    $this->get(route('frontend.checkout.index'))->assertOk();
});
