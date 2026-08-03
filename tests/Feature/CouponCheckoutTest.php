<?php

use App\Enums\CouponType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Services\Frontend\CheckoutService;

beforeEach(function () {
    Category::create(['name' => 'Tees', 'slug' => 'tees']);
    Brand::create(['name' => 'Acme', 'slug' => 'acme']);
});

function orderData(int $productId, float $price, ?string $coupon = null): array
{
    return array_filter([
        'customer' => ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'address' => 'x', 'city' => 'y'],
        'items' => [['id' => $productId, 'name' => 'x', 'price' => $price, 'size' => 'M', 'color' => 'black', 'qty' => 1]],
        'shipping_id' => null,
        'payment' => 'card',
        'coupon' => $coupon,
    ], fn ($v) => $v !== null);
}

it('validates a percentage coupon and returns the discount', function () {
    Coupon::create(['code' => 'SAVE10', 'type' => CouponType::Percentage, 'value' => 10, 'status' => true, 'used_count' => 0]);

    $res = app(CheckoutService::class)->validateCoupon('save10', 100);

    expect($res['valid'])->toBeTrue()
        ->and($res['discount'])->toBe(10.0)
        ->and($res['code'])->toBe('SAVE10');
});

it('rejects unknown and inactive coupons', function () {
    expect(app(CheckoutService::class)->validateCoupon('NOPE', 100)['valid'])->toBeFalse();

    Coupon::create(['code' => 'OFF', 'type' => CouponType::Fixed, 'value' => 5, 'status' => false]);
    expect(app(CheckoutService::class)->validateCoupon('OFF', 100)['valid'])->toBeFalse();
});

it('enforces minimum spend', function () {
    Coupon::create(['code' => 'BIG', 'type' => CouponType::Fixed, 'value' => 20, 'min_spend' => 50, 'status' => true]);

    expect(app(CheckoutService::class)->validateCoupon('BIG', 30)['valid'])->toBeFalse()
        ->and(app(CheckoutService::class)->validateCoupon('BIG', 60)['valid'])->toBeTrue();
});

it('applies the coupon to a placed order and counts the redemption', function () {
    $coupon = Coupon::create(['code' => 'TEN', 'type' => CouponType::Percentage, 'value' => 10, 'status' => true, 'used_count' => 0]);
    $product = Product::factory()->create(['status' => 'active', 'product_type' => 'single', 'stock' => 5, 'price' => 100, 'discount_type' => null, 'discount_amount' => 0]);

    $order = app(CheckoutService::class)->placeOrder(orderData($product->id, 100, 'TEN'));

    expect((float) $order->discount_total)->toBe(10.0)
        ->and($order->coupon_code)->toBe('TEN')
        ->and((float) $order->grand_total)->toBe(90.0)
        ->and($coupon->refresh()->used_count)->toBe(1);
});

it('ignores an invalid coupon at placement', function () {
    $product = Product::factory()->create(['status' => 'active', 'product_type' => 'single', 'stock' => 5, 'price' => 50]);

    $order = app(CheckoutService::class)->placeOrder(orderData($product->id, 50, 'BOGUS'));

    expect((float) $order->discount_total)->toBe(0.0)
        ->and($order->coupon_code)->toBeNull();
});

it('validates a coupon through the checkout endpoint', function () {
    Coupon::create(['code' => 'WEB', 'type' => CouponType::Percentage, 'value' => 15, 'status' => true]);

    $this->postJson(route('frontend.checkout.coupon'), ['code' => 'WEB', 'subtotal' => 100])
        ->assertOk()
        ->assertJson(['valid' => true, 'code' => 'WEB']);
});
