@extends('frontend.layouts.frontend')
@section('title', __('Track Order').' #UT-'.$order['id'].' — T-Shirt Shop')

@section('content')
{{-- Real progress for this order's status (OrderTrackingService::steps). --}}
<div class="ut-wrap anim-up" style="padding-top:28px;max-width:760px">
    <a href="{{ route('frontend.account.orders.show', $order['id']) }}" class="ut-link" style="margin-bottom:18px;display:inline-flex"><x-frontend.icon n="arrowL" :size="16" /> {{ __('Order details') }}</a>

    <div class="ut-card" style="padding:clamp(22px,4vw,32px);margin-bottom:20px">
        <div class="ut-row" style="justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:8px">
            <div><span class="ut-eyebrow" style="color:var(--blue)">{{ __('Order') }} #UT-{{ $order['id'] }}</span><h1 style="font-size:clamp(26px,3vw,34px);margin-top:8px">{{ $order['status'] === 'Shipped' ? __('On its way') : __($order['status']) }}</h1></div>
            <span class="ut-tag {{ $order['status'] === 'Delivered' ? 'ut-tag-success' : 'ut-tag-new' }}" style="align-self:center">{{ $order['status'] }}</span>
        </div>
        <div class="ut-row" style="gap:10px;margin-bottom:24px;background:var(--bg);border-radius:14px;padding:14px 16px">
            <span style="color:var(--blue)"><x-frontend.icon n="truck" :size="20" /></span>
            <span style="font-size:14.5px">{{ $order['status'] === 'Delivered' ? __('Delivered').' · ' : __('Estimated arrival').' · ' }}<b>{{ $order['eta'] }}</b></span>
        </div>

        {{-- vertical timeline --}}
        @include('frontend.orders._timeline', ['steps' => $order['steps']])
    </div>
    <div class="ut-row" style="gap:12px"><a href="{{ route('frontend.pages.contact') }}" class="ut-btn ut-btn-ghost ut-btn-lg">{{ __('Need help?') }}</a><a href="{{ route('frontend.account.orders.show', $order['id']) }}" class="ut-btn ut-btn-ink ut-btn-lg">{{ __('View order details') }}</a></div>
</div>
@endsection


