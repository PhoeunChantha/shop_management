@extends('frontend.layouts.frontend')
@section('title', __('Order').' '.$order->order_number.' — T-Shirt Shop')

@push('head')
    {{-- Private link: keep it out of search engines. --}}
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="ut-wrap anim-up" style="padding-top:28px;padding-bottom:40px;max-width:760px">
    <a href="{{ route('frontend.orders.track') }}" class="ut-link" style="margin-bottom:18px;display:inline-flex"><x-frontend.icon n="arrowL" :size="16" /> {{ __('Track another order') }}</a>

    <div class="ut-card" style="padding:clamp(22px,4vw,32px);margin-bottom:20px">
        <div class="ut-row" style="justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:8px">
            <div>
                <span class="ut-eyebrow" style="color:var(--blue)">{{ __('Order') }} {{ $order->order_number }}</span>
                <h1 style="font-size:clamp(26px,3vw,34px);margin-top:8px">{{ $statusLabel }}</h1>
                <p class="muted" style="font-size:13.5px;margin:6px 0 0">{{ __('Placed on') }} {{ ($order->placed_at ?? $order->created_at)->format('M j, Y') }}</p>
            </div>
            <span class="ut-tag ut-tag-new" style="align-self:center">{{ $statusLabel }}</span>
        </div>
        @if($eta)
            <div class="ut-row" style="gap:10px;margin:18px 0 24px;background:var(--bg);border-radius:14px;padding:14px 16px">
                <span style="color:var(--blue)"><x-frontend.icon n="truck" :size="20" /></span>
                <span style="font-size:14.5px">{{ __('Estimated delivery') }} · <b>{{ $eta }}</b></span>
            </div>
        @else
            <div style="height:16px"></div>
        @endif

        @include('frontend.orders._timeline', ['steps' => $steps])
    </div>

    <div class="ut-card" style="padding:28px;margin-bottom:20px">
        <h3 style="font-size:18px;margin-bottom:18px">{{ __('Order summary') }}</h3>
        <div class="ut-col" style="gap:12px">
            @foreach($order->details as $d)
                <div class="ut-row" style="justify-content:space-between;gap:12px">
                    <div style="min-width:0">
                        <div style="font-family:var(--font-head);font-weight:600;font-size:14.5px">{{ $d->name }}</div>
                        <div class="muted" style="font-size:12.5px">{{ $d->variant_label ? $d->variant_label.' · ' : '' }}{{ __('Qty') }} {{ $d->quantity }}</div>
                    </div>
                    <span style="font-family:var(--font-head);font-weight:600">{{ money((float) $d->line_total) }}</span>
                </div>
            @endforeach
        </div>
        <hr class="divider" style="margin:18px 0 14px">
        <div class="ut-col" style="gap:9px;font-size:14px">
            <div class="ut-row" style="justify-content:space-between"><span class="muted">{{ __('Subtotal') }}</span><span>{{ money((float) $order->subtotal) }}</span></div>
            @if((float) $order->discount_total > 0)
                <div class="ut-row" style="justify-content:space-between"><span class="muted">{{ __('Discount') }}</span><span style="color:#15803d">−{{ money((float) $order->discount_total) }}</span></div>
            @endif
            <div class="ut-row" style="justify-content:space-between"><span class="muted">{{ __('Shipping') }}</span><span>{{ (float) $order->shipping_total > 0 ? money((float) $order->shipping_total) : __('Free') }}</span></div>
            <div class="ut-row" style="justify-content:space-between"><span class="muted">{{ __('Tax') }}</span><span>{{ money((float) $order->tax_total) }}</span></div>
            <div class="ut-row" style="justify-content:space-between;margin-top:6px"><span style="font-family:var(--font-head);font-weight:700">{{ __('Total') }}</span><span style="font-family:var(--font-head);font-weight:700;font-size:18px">{{ money((float) $order->grand_total) }}</span></div>
        </div>
    </div>

    <div class="ut-card" style="padding:22px 28px;margin-bottom:20px">
        <div class="ut-row" style="gap:10px;align-items:flex-start">
            <span style="color:var(--text-3)"><x-frontend.icon n="pin" :size="18" /></span>
            <div style="font-size:14px">
                <b>{{ $order->customer_name }}</b><br>
                <span class="muted">{{ collect([$order->shipping_address, $order->shipping_city, $order->shipping_zip, $order->shipping_country])->filter()->join(', ') }}</span>
            </div>
        </div>
    </div>

    <div class="ut-row" style="gap:12px;flex-wrap:wrap">
        <a href="{{ route('frontend.pages.contact') }}" class="ut-btn ut-btn-ghost ut-btn-lg">{{ __('Need help?') }}</a>
        <a href="{{ route('frontend.home') }}" class="ut-btn ut-btn-ink ut-btn-lg">{{ __('Continue shopping') }}</a>
    </div>
</div>
@endsection
