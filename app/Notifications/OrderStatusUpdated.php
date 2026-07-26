<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly OrderStatus $status,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Pushed over websockets (Reverb) to the customer's private channel.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    /**
     * Persisted to the `notifications` table (and reused for broadcast payloads).
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'order',
            'icon' => 'box',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->status->label(),
            'title' => 'Order '.$this->order->order_number.' '.$this->status->label(),
            'body' => 'Your order is now '.strtolower($this->status->label()).'.',
            'url' => route('frontend.account.orders.show', $this->order->id),
        ];
    }
}
