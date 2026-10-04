<?php

declare(strict_types=1);

namespace App\Services\Frontend;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ShippingMethod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Order tracking shared by the checkout confirmation, the customer account
 * and the guest "Track order" page: real progress steps from the order's
 * status, a delivery estimate from its shipping method, and a private link
 * guests can use to come back to their order without an account.
 */
final class OrderTrackingService
{
    /** How long a guest's private tracking link stays valid. */
    public const LINK_DAYS = 90;

    /**
     * Find an order by its number + the email it was placed with (both must
     * match, so an order number alone reveals nothing).
     */
    public function lookup(string $number, string $email): ?Order
    {
        return Order::query()
            ->with('details')
            ->where('order_number', trim($number))
            ->whereRaw('LOWER(customer_email) = ?', [mb_strtolower(trim($email))])
            ->first();
    }

    public function guestUrl(Order $order): string
    {
        return URL::temporarySignedRoute(
            'frontend.orders.track.show',
            now()->addDays(self::LINK_DAYS),
            ['order' => $order->order_number],
        );
    }

    /**
     * Where a customer views this order: their account page when the order
     * belongs to an account, otherwise the private guest tracking link.
     */
    public function customerUrl(Order $order): string
    {
        return $order->user_id
            ? route('frontend.account.orders.show', $order->id)
            : $this->guestUrl($order);
    }

    /**
     * Progress steps for the order's real status.
     *
     * @return array<int, array{label: string, desc: string, date: string|null, icon: string, done: bool, current: bool}>
     */
    public function steps(Order $order): array
    {
        $status = $order->status instanceof OrderStatus ? $order->status : OrderStatus::tryFrom((string) $order->status);
        $placed = $order->placed_at ?? $order->created_at;
        $paid = $order->payment_status === 'paid' || in_array($status, [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered], true);
        $reached = match ($status) {
            OrderStatus::Delivered => 4,
            OrderStatus::Shipped => 3,
            OrderStatus::Processing => 2,
            OrderStatus::Paid => 1,
            default => $paid ? 1 : 0,
        };

        $steps = [
            [__('Order placed'), __('We received your order :number.', ['number' => $order->order_number]), $this->date($placed), 'checkC'],
            [__('Confirmed'), $paid ? __('Payment received and order accepted.') : __('Waiting for your payment to be confirmed.'), $this->date($order->paid_at), 'check'],
            [__('Processing'), __('Your items are being picked and packed.'), null, 'box'],
            [__('Shipped'), $order->tracking_number
                ? __('Handed to :carrier · tracking :number', ['carrier' => $order->carrier ?: $order->shipping_method ?: __('the courier'), 'number' => $order->tracking_number])
                : __('On its way to you.'), $this->date($order->shipped_at), 'truck'],
            [__('Delivered'), __('Delivered to your address.'), $this->date($order->fulfilled_at), 'home'],
        ];

        // A cancelled/refunded order stops after "Order placed".
        if (in_array($status, [OrderStatus::Cancelled, OrderStatus::Refunded], true)) {
            return [
                $this->step($steps[0], true, false),
                $this->step([$status->label(), $status === OrderStatus::Cancelled ? __('This order was cancelled.') : __('This order was refunded.'), $this->date($order->updated_at), 'close'], true, true),
            ];
        }

        return array_map(
            fn (array $step, int $i): array => $this->step($step, $i <= $reached, $i === $reached),
            $steps,
            array_keys($steps),
        );
    }

    /**
     * Customer-facing status label ("Awaiting payment" reads better than
     * "Pending" for an unpaid order).
     */
    public function statusLabel(Order $order): string
    {
        $status = $order->status instanceof OrderStatus ? $order->status : OrderStatus::tryFrom((string) $order->status);

        if ($status === OrderStatus::Pending) {
            return $order->payment_status === 'paid' ? __('Confirmed') : __('Awaiting payment');
        }

        return $status?->label() ?? ucfirst((string) $order->status);
    }

    /**
     * "Oct 6 – Oct 8, 2026" from the shipping method's delivery time
     * (e.g. "2-4 business days"), counted in business days from shipping
     * (or from the order date until it ships). Null when delivered,
     * cancelled, or the method has no parsable delivery time.
     */
    public function estimatedDelivery(Order $order): ?string
    {
        $status = $order->status instanceof OrderStatus ? $order->status : OrderStatus::tryFrom((string) $order->status);

        if (in_array($status, [OrderStatus::Delivered, OrderStatus::Cancelled, OrderStatus::Refunded], true) || ! $order->shipping_method) {
            return null;
        }

        $time = (string) ShippingMethod::query()->where('name', $order->shipping_method)->value('delivery_time');

        if (! preg_match('/(\d+)(?:\s*[-–to]+\s*(\d+))?/u', $time, $m)) {
            return null;
        }

        $from = Carbon::parse($order->shipped_at ?? $order->placed_at ?? $order->created_at);
        $start = $from->copy()->addWeekdays((int) $m[1]);
        $end = $from->copy()->addWeekdays((int) ($m[2] ?? $m[1]));

        if ($start->isSameDay($end)) {
            return $end->format('M j, Y');
        }

        return $start->format($start->year === $end->year ? 'M j' : 'M j, Y').' – '.$end->format('M j, Y');
    }

    /**
     * @param  array{0: string, 1: string, 2: string|null, 3: string}  $step
     * @return array{label: string, desc: string, date: string|null, icon: string, done: bool, current: bool}
     */
    private function step(array $step, bool $done, bool $current): array
    {
        return ['label' => $step[0], 'desc' => $step[1], 'date' => $step[2], 'icon' => $step[3], 'done' => $done, 'current' => $current];
    }

    private function date(mixed $date): ?string
    {
        return $date ? Carbon::parse($date)->format('M j, Y') : null;
    }
}
