@extends('frontend.layouts.frontend')
@section('title', 'Order Confirmed — T-Shirt Shop')

@section('content')
<div class="ut-wrap anim-up" style="padding-top:40px;padding-bottom:40px;max-width:720px">
    <div style="text-align:center">
        <div style="width:84px;height:84px;border-radius:50%;background:#dcfce7;color:#15803d;display:grid;place-items:center;margin:0 auto 20px;animation:pop .4s both"><x-frontend.icon n="check" :size="42" /></div>
        <span class="ut-eyebrow" style="color:var(--success)">Order confirmed</span>
        <h1 style="font-size:clamp(30px,4vw,44px);margin:10px 0 8px">Thanks for your order!</h1>
        <p class="muted">Order <b class="mono" style="color:var(--ink)">{{ $order->order_number }}</b> · A confirmation was sent to {{ $order->customer_email }}.</p>
    </div>

    <div class="ut-card" style="padding:28px;margin-top:30px">
        <div class="ut-row" style="justify-content:space-between;margin-bottom:24px"><h3 style="font-size:18px">Order tracking</h3><span class="ut-tag ut-tag-success">Confirmed</span></div>
        <div class="ut-row" style="position:relative">
            <div style="position:absolute;top:16px;left:12%;right:12%;height:3px;background:var(--border)"></div>
            @foreach(['Confirmed', 'Processing', 'Shipped', 'Delivered'] as $i => $s)
                <div style="flex:1;text-align:center;position:relative">
                    <span style="width:34px;height:34px;border-radius:50%;display:grid;place-items:center;margin:0 auto 10px;background:{{ $i === 0 ? 'var(--success)' : '#fff' }};color:{{ $i === 0 ? '#fff' : 'var(--text-3)' }};border:{{ $i === 0 ? 'none' : '2px solid var(--border)' }};font-family:var(--font-head);font-weight:700;font-size:13px;position:relative;z-index:1">@if($i === 0)<x-frontend.icon n="check" :size="17" />@else{{ $i + 1 }}@endif</span>
                    <div style="font-family:var(--font-head);font-weight:600;font-size:13px;color:{{ $i === 0 ? 'var(--ink)' : 'var(--text-2)' }}">{{ $s }}</div>
                </div>
            @endforeach
        </div>
        <div style="background:var(--bg);border-radius:var(--r-md);padding:16px;margin-top:24px" class="ut-row">
            <span style="color:var(--blue)"><x-frontend.icon n="truck" :size="20" /></span>
            <span style="margin-left:12px;font-size:14px">Estimated delivery <b>Jun 8 – Jun 10, 2026</b></span>
        </div>
    </div>

    <div class="ut-card" style="padding:28px;margin-top:20px">
        <h3 style="font-size:18px;margin-bottom:18px">Order summary</h3>
        <div class="ut-col" style="gap:12px">
            @foreach($order->details as $d)
                <div class="ut-row" style="justify-content:space-between;gap:12px">
                    <div class="ut-row" style="gap:12px;min-width:0">
                        @if($d->image)<img src="{{ Imageurl($d->image, 'products') }}" alt="" style="width:46px;height:56px;border-radius:8px;object-fit:cover;flex-shrink:0">@endif
                        <div style="min-width:0"><div style="font-family:var(--font-head);font-weight:600;font-size:14px">{{ $d->name }}</div><div class="muted" style="font-size:12.5px">{{ $d->variant_label ? $d->variant_label.' · ' : '' }}Qty {{ $d->quantity }}</div></div>
                    </div>
                    <span style="font-family:var(--font-head);font-weight:600;white-space:nowrap">${{ number_format($d->line_total, 2) }}</span>
                </div>
            @endforeach
        </div>
        <hr class="divider" style="margin:16px 0">
        <div class="ut-col" style="gap:9px">
            <div class="ut-row" style="justify-content:space-between;font-size:14px"><span class="muted">Subtotal</span><span>${{ number_format($order->subtotal, 2) }}</span></div>
            <div class="ut-row" style="justify-content:space-between;font-size:14px"><span class="muted">Shipping</span><span>{{ $order->shipping_total > 0 ? '$'.number_format($order->shipping_total, 2) : 'Free' }}</span></div>
            <div class="ut-row" style="justify-content:space-between;font-size:14px"><span class="muted">Tax</span><span>${{ number_format($order->tax_total, 2) }}</span></div>
            <hr class="divider" style="margin:4px 0">
            <div class="ut-row" style="justify-content:space-between"><span style="font-family:var(--font-head);font-weight:700;font-size:16px">Total</span><span style="font-family:var(--font-head);font-weight:700;font-size:20px">${{ number_format($order->grand_total, 2) }}</span></div>
        </div>
    </div>

    <div class="ut-row" style="gap:12px;margin-top:24px;justify-content:center">
        <a href="{{ route('frontend.account.orders') }}" class="ut-btn ut-btn-ink ut-btn-lg">View orders</a>
        <a href="{{ route('frontend.home') }}" class="ut-btn ut-btn-ghost ut-btn-lg">Continue shopping</a>
    </div>
</div>

@push('scripts')
<script>localStorage.removeItem('ut_cart'); /* order placed — clear bag */</script>
@endpush
@endsection


