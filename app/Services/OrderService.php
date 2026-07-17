<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    /**
     * Headline KPIs for the orders index stat bar.
     *
     * @return array<string, float|int>
     */
    public function stats(): array
    {
        $paidStates = [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value];

        $revenue = (float) Order::whereIn('payment_status', $paidStates)->sum('grand_total');
        $paidCount = Order::whereIn('payment_status', $paidStates)->count();

        return [
            'revenue' => $revenue,
            'orders' => Order::count(),
            'pending' => Order::where('status', OrderStatus::Pending->value)->count(),
            'aov' => $paidCount > 0 ? $revenue / $paidCount : 0.0,
            'refunded' => (float) Order::where('payment_status', PaymentStatus::Refunded->value)->sum('grand_total'),
        ];
    }

    /**
     * Paginated, filtered order list for the admin index.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Order::query()
            ->with('user:id,name,email')
            ->withCount('details')
            ->withSum('details', 'quantity')
            ->search($search)
            ->status($filters['status'] ?? null)
            ->when($filters['customer'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['price'] ?? null, function ($q, $range) {
                [$min, $max] = array_pad(explode('-', $range), 2, null);
                if ($min !== null && $min !== '') {
                    $q->where('grand_total', '>=', (float) $min);
                }
                if ($max !== null && $max !== '') {
                    $q->where('grand_total', '<=', (float) $max);
                }
            })
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForShow(string $id): Order
    {
        return Order::with(['details', 'user', 'coupon', 'events.actor', 'returnRequests.items'])->findOrFail($id);
    }

    public function findForInvoice(string $id): Order
    {
        return Order::with(['details', 'user', 'coupon'])->findOrFail($id);
    }

    public function findForPackingSlip(string $id): Order
    {
        return Order::with('details')->findOrFail($id);
    }

    public function customerStats(Order $order): ?object
    {
        if (! $order->user_id) {
            return null;
        }

        return Order::where('user_id', $order->user_id)
            ->selectRaw('count(*) as orders, coalesce(sum(grand_total),0) as spent')
            ->first();
    }

    /**
     * Price-range filter options: value ("min-max", open-ended max) => label.
     *
     * @return array<string, string>
     */
    public static function priceRanges(): array
    {
        return [
            '0-100' => '$0 - $100',
            '100-400' => '$100 - $400',
            '400-1000' => '$400 - $1,000',
            '1000-' => '$1,000+',
        ];
    }

    /**
     * Update fulfilment fields from the admin detail page.
     *
     * @param  array<string, mixed>  $data  Validated request data.
     */
    public function updateFulfilment(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $oldStatus = $order->status;
            $oldPayment = $order->payment_status;
            $oldTracking = $order->tracking_number;

            $newStatus = OrderStatus::from($data['status']);
            $newPayment = ! empty($data['payment_status'])
                ? PaymentStatus::from($data['payment_status'])
                : $this->derivePaymentStatus($order->payment_status, $newStatus);

            $order->status = $newStatus;
            $order->payment_status = $newPayment;
            $order->tracking_number = $data['tracking_number'] ?? null;
            $order->admin_note = $data['admin_note'] ?? null;

            if ($newPayment === PaymentStatus::Paid && $order->paid_at === null) {
                $order->paid_at = now();
            }

            $order->save();

            // Activity log entries for anything that actually changed.
            if ($oldStatus !== $newStatus) {
                $order->logEvent('status', 'Status → '.$newStatus->label(), 'Was '.$oldStatus->label());
            }
            if ($oldPayment !== $newPayment) {
                $order->logEvent('payment', 'Payment marked '.$newPayment->label());
            }
            if (filled($order->tracking_number) && $order->tracking_number !== $oldTracking) {
                $order->logEvent('fulfilment', 'Tracking number added', $order->tracking_number);
            }

            return $order;
        });
    }

    /**
     * Derive the payment status from a status change when the admin didn't set it.
     */
    private function derivePaymentStatus(PaymentStatus $current, OrderStatus $status): PaymentStatus
    {
        return match (true) {
            $status === OrderStatus::Refunded => PaymentStatus::Refunded,
            $current === PaymentStatus::Unpaid && in_array($status, [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered], true) => PaymentStatus::Paid,
            default => $current,
        };
    }
}
