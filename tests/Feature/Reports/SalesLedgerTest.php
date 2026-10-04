<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReportPreset;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\Admin\Reports\Comparison;
use App\Services\Admin\Reports\ReportFilters;
use App\Services\Admin\Reports\SalesLedger;
use Carbon\CarbonImmutable;

/**
 * Order worth subtotal 100, discount 10, tax 9, shipping 5 → grand total 104.
 */
function ledgerOrder(OrderStatus $status, PaymentStatus $payment, array $overrides = []): Order
{
    return Order::factory()->create(array_merge([
        'status' => $status->value,
        'payment_status' => $payment->value,
        'subtotal' => 100,
        'discount_total' => 10,
        'tax_total' => 9,
        'shipping_total' => 5,
        'grand_total' => 104,
        'placed_at' => now()->subDays(2),
    ], $overrides));
}

function ledgerRefund(Order $order, float $amount, string $refundStatus = 'refunded'): ReturnRequest
{
    return ReturnRequest::create([
        'return_number' => 'RMA-'.uniqid(),
        'order_id' => $order->id,
        'status' => $refundStatus === 'refunded' ? 'refunded' : 'received',
        'refund_status' => $refundStatus,
        'reason' => 'Test',
        'requested_amount' => $amount,
        'refund_amount' => $amount,
        'requested_at' => now(),
        'refunded_at' => now(),
    ]);
}

function ledgerSummary(): array
{
    return app(SalesLedger::class)->summary(ReportFilters::fromArray(['preset' => 'last_30_days']));
}

it('treats a paid order as a full sale with the waterfall reconciling', function () {
    ledgerOrder(OrderStatus::Delivered, PaymentStatus::Paid);

    $s = ledgerSummary();

    expect($s['orders'])->toBe(1)
        ->and($s['gross_sales'])->toBe(100.0)
        ->and($s['discounts'])->toBe(10.0)
        ->and($s['refunds'])->toBe(0.0)
        ->and($s['net_sales'])->toBe(90.0)
        ->and($s['total_sales'])->toBe(104.0)
        ->and($s['average_order_value'])->toBe(90.0);
});

it('implies a full refund for a paid order marked refunded with no recorded refund', function () {
    ledgerOrder(OrderStatus::Refunded, PaymentStatus::Refunded);

    $s = ledgerSummary();

    expect($s['gross_sales'])->toBe(100.0) // stays in gross history
        ->and($s['recorded_refunds'])->toBe(0.0)
        ->and($s['implied_refunds'])->toBe(104.0)
        ->and($s['refunds'])->toBe(104.0)
        ->and($s['merchandise_refunds'])->toBe(90.0)
        ->and($s['tax_shipping_refunds'])->toBe(14.0)
        ->and($s['net_sales'])->toBe(0.0)
        ->and($s['total_sales'])->toBe(0.0);
});

it('implies only the remaining amount when a refunded order has a partial recorded refund', function () {
    $order = ledgerOrder(OrderStatus::Refunded, PaymentStatus::Refunded);
    ledgerRefund($order, 30, 'partial');

    $s = ledgerSummary();

    expect($s['recorded_refunds'])->toBe(30.0)
        ->and($s['implied_refunds'])->toBe(74.0)
        ->and($s['refunds'])->toBe(104.0)
        ->and($s['total_sales'])->toBe(0.0);
});

it('does not invent a refund for a paid order that was cancelled', function () {
    ledgerOrder(OrderStatus::Cancelled, PaymentStatus::Paid);

    $s = ledgerSummary();

    expect($s['orders'])->toBe(1)
        ->and($s['refunds'])->toBe(0.0)
        ->and($s['net_sales'])->toBe(90.0)
        ->and($s['total_sales'])->toBe(104.0);
});

it('refunds a cancelled paid order only when its payment is marked refunded', function () {
    ledgerOrder(OrderStatus::Cancelled, PaymentStatus::Refunded);

    $s = ledgerSummary();

    expect($s['implied_refunds'])->toBe(104.0)
        ->and($s['total_sales'])->toBe(0.0);
});

it('uses the recorded amount for a cancelled order with an actual partial refund', function () {
    $order = ledgerOrder(OrderStatus::Cancelled, PaymentStatus::PartiallyRefunded);
    ledgerRefund($order, 40, 'partial');

    $s = ledgerSummary();

    expect($s['recorded_refunds'])->toBe(40.0)
        ->and($s['implied_refunds'])->toBe(0.0)
        ->and($s['net_sales'])->toBe(50.0)
        ->and($s['total_sales'])->toBe(64.0);
});

it('excludes unpaid cancelled orders and failed payments from sales entirely', function () {
    // Cancelled before payment.
    ledgerOrder(OrderStatus::Cancelled, PaymentStatus::Unpaid);
    // A failed PayWay attempt leaves the order unpaid/pending.
    $failed = ledgerOrder(OrderStatus::Pending, PaymentStatus::Unpaid);
    $failed->update(['status' => OrderStatus::Cancelled->value]);

    $s = ledgerSummary();

    expect($s['orders'])->toBe(0)
        ->and($s['gross_sales'])->toBe(0.0)
        ->and($s['refunds'])->toBe(0.0);
});

it('sums multiple recorded refunds on one order without double counting', function () {
    $order = ledgerOrder(OrderStatus::Delivered, PaymentStatus::PartiallyRefunded);
    ledgerRefund($order, 20, 'partial');
    ledgerRefund($order, 25, 'refunded');
    // Pending refunds are not money returned yet.
    ledgerRefund($order, 50, 'pending');

    $s = ledgerSummary();

    expect($s['orders'])->toBe(1)
        ->and($s['recorded_refunds'])->toBe(45.0)
        ->and($s['implied_refunds'])->toBe(0.0)
        ->and($s['refunds'])->toBe(45.0)
        ->and($s['net_sales'])->toBe(45.0);
});

it('caps recorded refunds at the order grand total', function () {
    $order = ledgerOrder(OrderStatus::Refunded, PaymentStatus::Refunded);
    ledgerRefund($order, 80, 'partial');
    ledgerRefund($order, 80, 'refunded');

    $s = ledgerSummary();

    expect($s['recorded_refunds'])->toBe(104.0)
        ->and($s['implied_refunds'])->toBe(0.0)
        ->and($s['refunds'])->toBe(104.0)
        ->and($s['total_sales'])->toBe(0.0);
});

it('does not add an implied refund when a return already refunded the whole order', function () {
    $order = ledgerOrder(OrderStatus::Refunded, PaymentStatus::Refunded);
    ledgerRefund($order, 104, 'refunded');

    $s = ledgerSummary();

    expect($s['recorded_refunds'])->toBe(104.0)
        ->and($s['implied_refunds'])->toBe(0.0)
        ->and($s['refunds'])->toBe(104.0);
});

it('only counts orders placed inside the window', function () {
    ledgerOrder(OrderStatus::Delivered, PaymentStatus::Paid, ['placed_at' => now()->subDays(60)]);
    ledgerOrder(OrderStatus::Delivered, PaymentStatus::Paid);

    expect(ledgerSummary()['orders'])->toBe(1);
});

it('defaults placed_at when an order is created without one', function () {
    $order = Order::factory()->create(['placed_at' => null]);

    expect($order->fresh()->placed_at)->not->toBeNull();
});

it('resolves presets, custom ranges and the previous period', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-14 10:00:00'));

    $month = ReportFilters::fromArray(['preset' => 'last_month']);
    expect($month->start->toDateString())->toBe('2026-09-01')
        ->and($month->end->toDateString())->toBe('2026-09-30');

    $quarter = ReportFilters::fromArray(['preset' => 'this_quarter']);
    expect($quarter->start->toDateString())->toBe('2026-10-01');

    $custom = ReportFilters::fromArray(['start_date' => '2026-10-10', 'end_date' => '2026-10-01']);
    expect($custom->preset)->toBe(ReportPreset::Custom)
        ->and($custom->start->toDateString())->toBe('2026-10-01')
        ->and($custom->end->toDateString())->toBe('2026-10-10')
        ->and($custom->previous()->start->toDateString())->toBe('2026-09-21')
        ->and($custom->previous()->end->toDateString())->toBe('2026-09-30');

    expect(ReportFilters::fromArray([])->preset)->toBe(ReportPreset::Last30Days);

    $this->travelBack();
});

it('never fabricates a percentage change against a zero base', function () {
    expect(Comparison::of(50, 0)['change'])->toBeNull()
        ->and(Comparison::of(50, 0)['direction'])->toBe('flat')
        ->and(Comparison::of(110, 100)['change'])->toBe(10.0)
        ->and(Comparison::of(90, 100)['direction'])->toBe('down')
        ->and(Comparison::percent(1, 0))->toBeNull();
});
