<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Admin\SettingService;
use App\Services\Frontend\OrderTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Your order is now …" — sent when an order's status changes, to customers
 * who ticked "Email me order updates" at checkout (guests included).
 */
class OrderStatusMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Order :number — :status', [
                'number' => $this->order->order_number,
                'status' => $this->order->status->label(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.status',
            with: [
                'order' => $this->order,
                'headline' => $this->headline(),
                'storeName' => app(SettingService::class)->siteName(),
                'url' => app(OrderTrackingService::class)->customerUrl($this->order),
            ],
        );
    }

    private function headline(): string
    {
        return match ($this->order->status) {
            OrderStatus::Paid => __('We received your payment — your order is confirmed.'),
            OrderStatus::Processing => __('Your order is being picked and packed.'),
            OrderStatus::Shipped => __('Your order is on its way.'),
            OrderStatus::Delivered => __('Your order has been delivered. Enjoy!'),
            OrderStatus::Cancelled => __('Your order was cancelled.'),
            OrderStatus::Refunded => __('Your order was refunded.'),
            default => __('There is an update on your order.'),
        };
    }
}
