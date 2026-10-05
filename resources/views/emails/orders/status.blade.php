<x-mail::message>
# {{ $headline }}

Hi {{ $order->customer_name ?: 'there' }}, order **{{ $order->order_number }}** is now **{{ $order->status->label() }}**.

@if ($order->tracking_number)
**Carrier:** {{ $order->carrier ?: $order->shipping_method ?: 'Courier' }}<br>
**Tracking number:** {{ $order->tracking_number }}
@endif

**Order total:** {{ money($order->grand_total) }}

<x-mail::button :url="$url">
View your order
</x-mail::button>

You are receiving this because you chose to get order updates at checkout.

Thanks for shopping with us,<br>
The {{ $storeName }} team
</x-mail::message>
