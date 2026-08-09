<x-mail::message>
# Thanks for your order, {{ $order->customer_name ?: 'friend' }}!

We’ve received order **{{ $order->order_number }}** and are getting it ready. Your invoice is attached as a PDF.

<x-mail::table>
| Item | Qty | Amount |
| :--- | :-: | -----: |
@foreach ($order->details as $item)
| {{ $item->name }}{{ $item->variant_label ? ' ('.$item->variant_label.')' : '' }} | {{ $item->quantity }} | {{ money($item->line_total) }} |
@endforeach
</x-mail::table>

**Subtotal:** {{ money($order->subtotal) }}
@if ($order->discount_total > 0)
**Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}:** −{{ money($order->discount_total) }}
@endif
**Shipping:** {{ $order->shipping_total > 0 ? money($order->shipping_total) : 'Free' }}
**Tax:** {{ money($order->tax_total) }}
**Total:** {{ money($order->grand_total) }}

<x-mail::button :url="$url">
View your order
</x-mail::button>

Thanks for shopping with us,<br>
The {{ $storeName }} team
</x-mail::message>
