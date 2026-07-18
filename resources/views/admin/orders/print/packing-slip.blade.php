<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Packing slip {{ $order->order_number }}</title>
    @include('admin.orders.print._styles')
</head>
<body>
    <div class="toolbar no-print">
        <a href="{{ route('admin.orders.show', $order->id) }}"><span>&larr;</span> Back to order</a>
        <button type="button" class="btn-print" onclick="window.print()">🖨 Print / Save PDF</button>
    </div>

    <div class="sheet">
        {{-- Header --}}
        <div class="doc-head">
            <div class="brand">
                @if ($store['logo'])
                    <img src="{{ $store['logo'] }}" alt="{{ $store['name'] }}">
                @endif
                <div class="brand__meta">
                    <div class="brand__name" style="font-size:14px;">{{ $store['name'] }}</div>
                    @if ($store['address']){!! nl2br(e($store['address'])) !!}@endif
                </div>
            </div>
            <div class="doc-title">
                <h1>PACKING SLIP</h1>
                <div class="num">{{ $order->order_number }}</div>
                <div class="date">{{ ($order->placed_at ?? $order->created_at)?->format('F j, Y') }}</div>
            </div>
        </div>

        {{-- Parties --}}
        <div class="parties">
            <div class="party">
                <h3>Ship to</h3>
                <p class="name">{{ $order->customer_name }}</p>
                <p>
                    {{ $order->shipping_address }}<br>
                    {{ collect([$order->shipping_city, $order->shipping_zip])->filter()->join(', ') }}<br>
                    {{ $order->shipping_country }}<br>
                    @if ($order->customer_phone){{ $order->customer_phone }}@endif
                </p>
            </div>
            <div class="party">
                <h3>Order</h3>
                <p>
                    <strong>Number:</strong> {{ $order->order_number }}<br>
                    <strong>Method:</strong> {{ $order->shipping_method ? ucfirst($order->shipping_method) : 'Standard' }}<br>
                    @if ($order->carrier)<strong>Carrier:</strong> {{ $order->carrier }}<br>@endif
                    @if ($order->tracking_number)<strong>Tracking:</strong> {{ $order->tracking_number }}<br>@endif
                    @if ($order->shipped_at)<strong>Shipped:</strong> {{ $order->shipped_at->format('M d, Y') }}<br>@endif
                    <strong>Items:</strong> {{ $order->details->sum('quantity') }} units
                </p>
            </div>
        </div>

        {{-- Items (no prices) --}}
        <table class="items">
            <thead>
                <tr>
                    <th style="width:34px;" class="c">#</th>
                    <th>Product</th>
                    <th style="width:130px;">SKU</th>
                    <th class="c" style="width:70px;">Qty</th>
                    <th class="c" style="width:60px;">✓</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->details as $item)
                    <tr>
                        <td class="c mono">{{ $loop->iteration }}</td>
                        <td>
                            <div class="item-name">{{ $item->name }}</div>
                            @if ($item->variant_label)<div class="item-variant">{{ $item->variant_label }}</div>@endif
                        </td>
                        <td class="item-sku">{{ $item->sku ?: '—' }}</td>
                        <td class="c mono" style="font-size:15px;font-weight:800;">{{ $item->quantity }}</td>
                        <td class="c" style="font-size:16px;color:#d1d5db;">☐</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($order->customer_note)
            <div class="note-box">
                <strong>Customer note</strong>
                {{ $order->customer_note }}
            </div>
        @endif

        <div class="doc-foot">
            Picked by ____________________ · Checked by ____________________ · {{ now()->format('M j, Y') }}
        </div>
    </div>
</body>
</html>
