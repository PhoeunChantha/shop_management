<?php

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Arr;

$existing = Payment::pluck('tran_id')->all();
$paidStatuses = ['paid', 'partially_refunded', 'refunded'];
$created = 0;

Order::whereNotIn('order_number', $existing ?: ['__none__'])->chunkById(200, function ($orders) use ($paidStatuses, &$created): void {
    foreach ($orders as $order) {
        $status = $order->payment_status instanceof \App\Enums\PaymentStatus ? $order->payment_status->value : (string) $order->payment_status;
        $isPaid = in_array($status, $paidStatuses, true);

        Payment::create([
            'order_id' => $order->id,
            'gateway' => 'payway',
            'tran_id' => $order->order_number,
            'payment_option' => Arr::random(['cards', 'abapay', 'khqr']),
            'amount' => $order->grand_total,
            'currency' => 'USD',
            'status' => $isPaid ? 'completed' : Arr::random(['pending', 'failed']),
            'created_at' => $order->placed_at ?? $order->created_at,
            'updated_at' => $order->placed_at ?? $order->created_at,
        ]);
        $created++;
    }
});

echo "Backfilled {$created} payments. Total payments: ".Payment::count().PHP_EOL;
