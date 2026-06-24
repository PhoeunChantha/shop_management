@extends('frontend.layouts.frontend')
@section('title', 'Order #UT-'.$order['id'].' — T-Shirt Shop')

@push('head')
<style>
    .ut-od-grid { display:grid; grid-template-columns:1fr 340px; gap:28px; align-items:start; }
    @media (max-width:1024px){ .ut-od-grid{ grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
@php
    $subtotal = collect($order['items'])->sum(fn($i) => $i['price'] * $i['qty']);
    $shipping = $subtotal >= 75 ? 0 : 6.95;
    $tax = round($subtotal * 0.08, 2);
    $total = $subtotal + $shipping + $tax;
@endphp
<div class="ut-wrap anim-up" style="padding-top:28px;max-width:920px">
    <a href="{{ route('frontend.account.orders') }}" class="ut-link" style="margin-bottom:18px;display:inline-flex"><x-frontend.icon n="arrowL" :size="16" /> Back to orders</a>
    <div class="ut-row" style="justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:24px">
        <div><h1 style="font-size:32px">Order #UT-{{ $order['id'] }}</h1><p class="muted" style="margin-top:6px">Placed {{ $order['date'] }} · {{ collect($order['items'])->sum('qty') }} items</p></div>
        <div class="ut-row" style="gap:10px">
            <span class="ut-tag {{ $order['status'] === 'Delivered' ? 'ut-tag-success' : 'ut-tag-new' }}" style="align-self:center">{{ $order['status'] }}</span>
            <a href="{{ route('frontend.account.orders.tracking', $order['id']) }}" class="ut-btn ut-btn-ink ut-btn-sm"><x-frontend.icon n="truck" :size="15" /> Track</a>
        </div>
    </div>

    <div class="ut-od-grid">
        <div class="ut-col" style="gap:16px">
            <div class="ut-card" style="padding:6px 22px">
                @foreach($order['items'] as $it)
                    @php $p = $products[$it['pid']] ?? null; @endphp
                    <div class="ut-row" style="gap:14px;padding:16px 0;{{ !$loop->last ? 'border-bottom:1px solid var(--border-2)' : '' }}">
                        <x-frontend.ph :tint="$p['tint'] ?? ''" :dark="$p['dark'] ?? false" style="width:64px;height:80px;border-radius:12px;flex-shrink:0" />
                        <div style="flex:1">
                            <div style="font-family:var(--font-head);font-weight:600">{{ $it['name'] }}</div>
                            <div class="muted" style="font-size:13px;margin-top:3px">{{ $colors[$it['color']]['name'] ?? $it['color'] }} · Size {{ $it['size'] }} · Qty {{ $it['qty'] }}</div>
                            @if($order['status'] === 'Delivered')
                                <a href="{{ route('frontend.account.orders.review', [$order['id'], $it['pid']]) }}" class="ut-link" style="font-size:13px;margin-top:8px;display:inline-flex"><x-frontend.icon n="star" :size="13" /> Write a review</a>
                            @endif
                        </div>
                        <span style="font-family:var(--font-head);font-weight:700">${{ $it['price'] * $it['qty'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="ut-card" style="padding:22px">
                <h3 style="font-size:16px;margin-bottom:14px">Shipping details</h3>
                <div class="ut-row" style="gap:12px;align-items:flex-start"><span style="color:var(--text-2);margin-top:2px"><x-frontend.icon n="pin" :size="18" /></span><p class="muted" style="font-size:14px;margin:0;line-height:1.6">{{ $order['address'] }}</p></div>
                <hr class="divider" style="margin:16px 0">
                <div class="ut-row" style="gap:12px"><span style="color:var(--text-2)"><x-frontend.icon n="truck" :size="18" /></span><div><div style="font-family:var(--font-head);font-weight:600;font-size:14px">{{ $order['courier'] }}</div><div class="muted" style="font-size:13px">Tracking: <span class="mono">{{ $order['tracking'] }}</span></div></div></div>
            </div>
        </div>

        <div class="ut-card" style="padding:24px">
            <h3 style="font-size:16px;margin-bottom:16px">Payment summary</h3>
            <div class="ut-col" style="gap:11px">
                <div class="ut-row" style="justify-content:space-between;font-size:14.5px"><span class="muted">Subtotal</span><span style="font-weight:600">${{ number_format($subtotal, 2) }}</span></div>
                <div class="ut-row" style="justify-content:space-between;font-size:14.5px"><span class="muted">Shipping</span><span style="font-weight:600;{{ $shipping === 0 ? 'color:#15803d' : '' }}">{{ $shipping === 0 ? 'Free' : '$'.number_format($shipping, 2) }}</span></div>
                <div class="ut-row" style="justify-content:space-between;font-size:14.5px"><span class="muted">Tax</span><span style="font-weight:600">${{ number_format($tax, 2) }}</span></div>
                <hr class="divider" style="margin:6px 0">
                <div class="ut-row" style="justify-content:space-between"><span style="font-family:var(--font-head);font-weight:700;font-size:17px">Total</span><span style="font-family:var(--font-head);font-weight:700;font-size:24px">${{ number_format($total, 2) }}</span></div>
            </div>
            <div class="ut-row" style="gap:10px;margin-top:16px;padding:12px 14px;background:var(--bg);border-radius:12px"><span style="color:var(--text-2)"><x-frontend.icon n="card" :size="18" /></span><span style="font-size:13.5px;font-family:var(--font-head);font-weight:600">Visa •••• 4242</span></div>
            <button type="button" class="ut-btn ut-btn-ghost ut-btn-block" style="margin-top:16px" onclick="utToast('Invoice downloaded')">Download invoice</button>
            <button type="button" class="ut-btn ut-btn-ghost ut-btn-block" style="margin-top:10px" onclick="utToast('Return started')">Return or exchange</button>
        </div>
    </div>
</div>
@endsection


