<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.5;
        }
        .wrap { padding: 8px 4px; }

        /* Header (table for two columns — dompdf has no flexbox) */
        .head { width: 100%; border-collapse: collapse; border-bottom: 2px solid #111827; padding-bottom: 8px; }
        .head td { vertical-align: top; padding: 0 0 14px; }
        .brand-name { font-size: 19px; font-weight: bold; color: #111827; }
        .brand-meta { margin-top: 5px; font-size: 10.5px; color: #6b7280; line-height: 1.5; }
        .brand img { max-height: 44px; max-width: 150px; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; font-size: 26px; font-weight: bold; letter-spacing: 2px; color: #111827; }
        .doc-title .num { margin-top: 4px; font-size: 12px; font-weight: bold; color: #374151; }
        .doc-title .date { margin-top: 2px; font-size: 11px; color: #6b7280; }
        .pill { display: inline-block; margin-top: 6px; padding: 3px 10px; border-radius: 999px;
            font-size: 10px; font-weight: bold; text-transform: uppercase; border: 1px solid currentColor; }

        /* Parties */
        .parties { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .parties td { vertical-align: top; width: 33%; padding-right: 18px; }
        .parties h3 { margin: 0 0 5px; font-size: 9.5px; font-weight: bold;
            text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; }
        .parties p { margin: 0; font-size: 11.5px; color: #374151; line-height: 1.6; }
        .parties .name { font-weight: bold; color: #111827; }

        /* Items */
        table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.items thead th { background: #111827; color: #fff; font-size: 10px; font-weight: bold;
            text-transform: uppercase; letter-spacing: 1px; padding: 9px 11px; text-align: left; }
        table.items thead th.r { text-align: right; }
        table.items thead th.c { text-align: center; }
        table.items tbody td { padding: 10px 11px; border-bottom: 1px solid #eceef1; vertical-align: top; }
        table.items tbody td.r { text-align: right; }
        table.items tbody td.c { text-align: center; }
        .item-name { font-weight: bold; color: #111827; }
        .item-variant { font-size: 10.5px; color: #6b7280; }
        .item-sku { font-size: 10px; color: #9ca3af; }

        /* Totals */
        .totals { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .totals .spacer { width: 62%; }
        .totals table { width: 100%; border-collapse: collapse; }
        .totals td.lbl { color: #6b7280; padding: 5px 4px; }
        .totals td.val { text-align: right; font-weight: bold; color: #111827; padding: 5px 4px; }
        .totals tr.grand td { border-top: 2px solid #111827; font-size: 15px; font-weight: bold;
            color: #111827; padding-top: 10px; }

        .note-box { margin-top: 20px; padding: 12px 14px; background: #f9fafb; border: 1px solid #eceef1;
            font-size: 11px; color: #4b5563; }
        .note-box strong { display: block; color: #111827; margin-bottom: 3px; text-transform: uppercase;
            font-size: 10px; letter-spacing: 1px; }

        .foot { margin-top: 28px; padding-top: 14px; border-top: 1px solid #eceef1; text-align: center;
            font-size: 10.5px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="wrap">
    {{-- Header --}}
    <table class="head">
        <tr>
            <td>
                @if ($store['logo'])
                    <img src="{{ $store['logo'] }}" alt="{{ $store['name'] }}"><br>
                @endif
                <div class="brand-name">{{ $store['name'] }}</div>
                <div class="brand-meta">
                    @if ($store['address']){!! nl2br(e($store['address'])) !!}<br>@endif
                    @if ($store['email']){{ $store['email'] }}@endif
                    @if ($store['phone']) · {{ $store['phone'] }}@endif
                </div>
            </td>
            <td class="doc-title">
                <h1>INVOICE</h1>
                <div class="num">{{ $order->order_number }}</div>
                <div class="date">Issued {{ ($order->placed_at ?? $order->created_at)?->format('F j, Y') }}</div>
                @php($status = is_object($order->payment_status) ? $order->payment_status->value : $order->payment_status)
                @php($pc = $status === 'paid' ? '#047857' : ($status === 'unpaid' ? '#b45309' : '#be123c'))
                <span class="pill" style="color: {{ $pc }};">{{ is_object($order->payment_status) ? $order->payment_status->label() : ucfirst((string) $status) }}</span>
            </td>
        </tr>
    </table>

    {{-- Parties --}}
    <table class="parties">
        <tr>
            <td>
                <h3>Billed to</h3>
                <p class="name">{{ $order->customer_name }}</p>
                <p>
                    {{ $order->customer_email }}<br>
                    @if ($order->customer_phone){{ $order->customer_phone }}@endif
                </p>
            </td>
            <td>
                <h3>Ship to</h3>
                <p>
                    {{ $order->shipping_address }}<br>
                    {{ collect([$order->shipping_city, $order->shipping_zip])->filter()->join(', ') }}<br>
                    {{ $order->shipping_country }}
                </p>
            </td>
            <td>
                <h3>Details</h3>
                <p>
                    <strong>Status:</strong> {{ is_object($order->status) ? $order->status->label() : ucfirst((string) $order->status) }}<br>
                    <strong>Payment:</strong> {{ $order->payment_method ? strtoupper($order->payment_method) : '—' }}<br>
                    @if ($order->shipping_method)<strong>Shipping:</strong> {{ ucfirst($order->shipping_method) }}@endif
                </p>
            </td>
        </tr>
    </table>

    {{-- Items --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:30px;" class="c">#</th>
                <th>Description</th>
                <th class="c" style="width:50px;">Qty</th>
                <th class="r" style="width:95px;">Unit price</th>
                <th class="r" style="width:105px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->details as $item)
                <tr>
                    <td class="c">{{ $loop->iteration }}</td>
                    <td>
                        <div class="item-name">{{ $item->name }}</div>
                        @if ($item->variant_label)<div class="item-variant">{{ $item->variant_label }}</div>@endif
                        @if ($item->sku)<div class="item-sku">SKU: {{ $item->sku }}</div>@endif
                    </td>
                    <td class="c">{{ $item->quantity }}</td>
                    <td class="r">${{ number_format($item->price, 2) }}</td>
                    <td class="r">${{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals">
        <tr>
            <td class="spacer"></td>
            <td>
                <table>
                    <tr><td class="lbl">Subtotal</td><td class="val">${{ number_format($order->subtotal, 2) }}</td></tr>
                    @if ($order->discount_total > 0)
                        <tr><td class="lbl">Discount{{ $order->coupon_code ? ' (' . $order->coupon_code . ')' : '' }}</td>
                            <td class="val">−${{ number_format($order->discount_total, 2) }}</td></tr>
                    @endif
                    <tr><td class="lbl">Shipping</td><td class="val">{{ $order->shipping_total > 0 ? '$' . number_format($order->shipping_total, 2) : 'Free' }}</td></tr>
                    <tr><td class="lbl">Tax</td><td class="val">${{ number_format($order->tax_total, 2) }}</td></tr>
                    <tr class="grand"><td class="lbl" style="color:#111827;">Total</td><td class="val">${{ number_format($order->grand_total, 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($order->customer_note)
        <div class="note-box">
            <strong>Customer note</strong>
            {{ $order->customer_note }}
        </div>
    @endif

    <div class="foot">
        Thank you for your business! · {{ $store['name'] }}
    </div>
</div>
</body>
</html>
