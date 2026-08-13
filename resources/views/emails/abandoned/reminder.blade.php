<x-mail::message>
# {{ __('You left something in your bag') }}

{{ __('Hi') }} {{ $cart->customer_name ?: __('there') }},

{{ __('Your bag at :store is still waiting for you.', ['store' => $storeName]) }}

@if($cart->items->isNotEmpty())
<x-mail::table>
| {{ __('Item') }} | {{ __('Qty') }} |
|:-------|:-----:|
@foreach($cart->items as $item)
| {{ $item->name ?: __('Item') }} | {{ $item->quantity ?: 1 }} |
@endforeach
</x-mail::table>
@endif

<x-mail::button :url="$url">
{{ __('Return to your bag') }}
</x-mail::button>

{{ __('See you soon,') }}<br>
{{ $storeName }}
</x-mail::message>
