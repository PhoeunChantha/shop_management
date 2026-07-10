<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $order->order_number }}</title>
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
                @else
                    <div>
                        <div class="brand__name">{{ $store['name'] }}</div>
                    </div>
                @endif
                <div class="brand__meta">
                    @if ($store['logo'])<div class="brand__name" style="font-size:14px;">{{ $store['name'] }}</div>@endif
                    @if ($store['address']){!! nl2br(e($store['address'])) !!}<br>@endif
                    @if ($store['email']){{ $store['email'] }}@endif
                    @if ($store['phone']) · {{ $store['phone'] }}@endif
                </div>
            </div>
            <div class="doc-title">
                <h1>INVOICE</h1>
                <div class="num">{{ $order->order_number }}</div>
                <div class="date">Issued {{ ($order->placed_at ?? $order->created_at)?->format('F j, Y') }}</div>
                @php($pc = $order->payment_status->value === 'paid' ? '#047857' : ($order->payment_status->value === 'unpaid' ? '#b45309' : '#be123c'))
                <span class="pill" style="color: {{ $pc }};">{{ $order->payment_status->label() }}</span>
            </div>
        </div>

        {{-- Parties --}}
        <div class="parties">
            <div class="party">
                <h3>Billed to</h3>
                <p class="name">{{ $order->customer_name }}</p>
                <p>
                    {{ $order->customer_email }}<br>
                    @if ($order->customer_phone){{ $order->customer_phone }}@endif
                </p>
            </div>
            <div class="party">
                <h3>Ship to</h3>
                <p>
                    {{ $order->shipping_address }}<br>
                    {{ collect([$order->shipping_city, $order->shipping_zip])->filter()->join(', ') }}<br>
                    {{ $order->shipping_country }}
                </p>
            </div>
            <div class="party">
                <h3>Details</h3>
                <p>
                    <strong>Status:</strong> {{ $order->status->label() }}<br>
                    <strong>Payment:</strong> {{ $order->payment_method ? strtoupper($order->payment_method) : '—' }}<br>
                    @if ($order->shipping_method)<strong>Shipping:</strong> {{ ucfirst($order->shipping_method) }}@endif
                </p>
            </div>
        </div>

        {{-- Items --}}
        <table class="items">
            <thead>
                <tr>
                    <th style="width:34px;" class="c">#</th>
                    <th>Description</th>
                    <th class="c" style="width:60px;">Qty</th>
                    <th class="r" style="width:110px;">Unit price</th>
                    <th class="r" style="width:120px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->details as $item)
                    <tr>
                        <td class="c mono">{{ $loop->iteration }}</td>
                        <td>
                            <div class="item-name">{{ $item->name }}</div>
                            @if ($item->variant_label)<div class="item-variant">{{ $item->variant_label }}</div>@endif
                            @if ($item->sku)<div class="item-sku">SKU: {{ $item->sku }}</div>@endif
                        </td>
                        <td class="c mono">{{ $item->quantity }}</td>
                        <td class="r mono">${{ number_format($item->price, 2) }}</td>
                        <td class="r mono">${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals">
            <table>
                <tr><td class="lbl">Subtotal</td><td class="val mono">${{ number_format($order->subtotal, 2) }}</td></tr>
                @if ($order->discount_total > 0)
                    <tr><td class="lbl">Discount{{ $order->coupon_code ? ' (' . $order->coupon_code . ')' : '' }}</td>
                        <td class="val mono">−${{ number_format($order->discount_total, 2) }}</td></tr>
                @endif
                <tr><td class="lbl">Shipping</td><td class="val mono">{{ $order->shipping_total > 0 ? '$' . number_format($order->shipping_total, 2) : 'Free' }}</td></tr>
                <tr><td class="lbl">Tax</td><td class="val mono">${{ number_format($order->tax_total, 2) }}</td></tr>
                <tr class="grand"><td class="lbl" style="color:#111827;">Total</td><td class="val mono">${{ number_format($order->grand_total, 2) }}</td></tr>
            </table>
        </div>

        @if ($order->customer_note)
            <div class="note-box">
                <strong>Customer note</strong>
                {{ $order->customer_note }}
            </div>
        @endif

        <div class="doc-foot">
            Thank you for your business! · Generated {{ now()->format('M j, Y g:i A') }}
        </div>
    </div>
</body>
</html>
