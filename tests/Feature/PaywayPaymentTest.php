<?php

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Frontend\CheckoutService;
use App\Services\Frontend\PaywayService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.payway.base_url' => 'https://checkout-sandbox.payway.com.kh',
        'services.payway.merchant_id' => 'merchant123',
        'services.payway.api_key' => 'secretkey',
        'services.payway.currency' => 'USD',
    ]);
});

function pendingPayment(Order $order): void
{
    Payment::create([
        'order_id' => $order->id,
        'gateway' => 'payway',
        'tran_id' => $order->order_number,
        'amount' => (float) $order->grand_total,
        'currency' => 'USD',
        'status' => 'pending',
    ]);
}

it('signs the purchase hash with HMAC-SHA512 in field order', function () {
    $fields = array_fill_keys([
        'req_time', 'merchant_id', 'tran_id', 'amount', 'items', 'shipping',
        'ctid', 'pwt', 'firstname', 'lastname', 'email', 'phone', 'type',
        'payment_option', 'return_url', 'cancel_url', 'continue_success_url',
        'return_deeplink', 'currency', 'custom_fields', 'return_params',
    ], '');
    $fields['tran_id'] = 'UT-2026-000001';
    $fields['amount'] = '10.00';

    $expected = base64_encode(hash_hmac('sha512', 'UT-2026-00000110.00', 'secretkey', true));

    expect(app(PaywayService::class)->hash($fields))->toBe($expected);
});

it('marks the order paid when PayWay approves', function () {
    Http::fake(['*' => Http::response(['status' => ['code' => '00'], 'data' => ['payment_status' => 'APPROVED']], 200)]);

    $order = Order::factory()->create(['payment_status' => 'unpaid', 'grand_total' => 10]);
    pendingPayment($order);

    expect(app(PaywayService::class)->confirm($order->refresh()))->toBeTrue();
    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Paid);
    expect(Payment::first()->status)->toBe('completed');
});

it('leaves the order unpaid when PayWay declines', function () {
    Http::fake(['*' => Http::response(['data' => ['payment_status' => 'DECLINED']], 200)]);

    $order = Order::factory()->create(['payment_status' => 'unpaid', 'grand_total' => 10]);
    pendingPayment($order);

    expect(app(PaywayService::class)->confirm($order->refresh()))->toBeFalse();
    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Unpaid);
    expect(Payment::first()->status)->toBe('failed');
});

it('confirms payment via the CSRF-exempt callback', function () {
    Http::fake(['*' => Http::response(['data' => ['payment_status' => 'APPROVED']], 200)]);

    $order = Order::factory()->create(['payment_status' => 'unpaid', 'grand_total' => 10]);
    pendingPayment($order);

    $this->post(route('frontend.payment.callback'), ['tran_id' => $order->order_number])
        ->assertOk();

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Paid);
});

it('detects online vs manual payment methods', function () {
    Setting::set('payment_methods', json_encode([
        ['id' => 'aba', 'name' => 'ABA PayWay', 'code' => 'aba', 'type' => 'online', 'status' => true, 'sort_order' => 1],
        ['id' => 'manual', 'name' => 'Manual', 'code' => 'manual', 'type' => 'manual', 'status' => true, 'sort_order' => 2],
    ]), 'payment');

    $checkout = app(CheckoutService::class);

    expect($checkout->isOnlineMethod('aba'))->toBeTrue()
        ->and($checkout->isOnlineMethod('manual'))->toBeFalse()
        ->and($checkout->isOnlineMethod(null))->toBeFalse();
});
