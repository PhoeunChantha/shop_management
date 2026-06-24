@extends('frontend.layouts.frontend')
@section('title', 'Track Order #UT-'.$order['id'].' — T-Shirt Shop')

@section('content')
@php
    $steps = [
        ['Order placed', 'We received your order #UT-'.$order['id'], $order['date'], 'checkC'],
        ['Confirmed', 'Payment confirmed and order accepted', $order['date'], 'check'],
        ['Processing', 'Your items are being picked & packed', 'Jun 4, 2026', 'box'],
        ['Shipped', 'Handed to '.$order['courier'].' · '.$order['tracking'], 'Jun 5, 2026', 'truck'],
        ['Delivered', $order['status'] === 'Delivered' ? 'Left at front door' : 'Estimated '.$order['eta'], '', 'home'],
    ];
@endphp
<div class="ut-wrap anim-up" style="padding-top:28px;max-width:760px">
    <a href="{{ route('frontend.account.orders.show', $order['id']) }}" class="ut-link" style="margin-bottom:18px;display:inline-flex"><x-frontend.icon n="arrowL" :size="16" /> Order details</a>

    <div class="ut-card" style="padding:clamp(22px,4vw,32px);margin-bottom:20px">
        <div class="ut-row" style="justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:8px">
            <div><span class="ut-eyebrow" style="color:var(--blue)">Order #UT-{{ $order['id'] }}</span><h1 style="font-size:clamp(26px,3vw,34px);margin-top:8px">{{ $order['status'] === 'Delivered' ? 'Delivered' : 'On its way' }}</h1></div>
            <span class="ut-tag {{ $order['status'] === 'Delivered' ? 'ut-tag-success' : 'ut-tag-new' }}" style="align-self:center">{{ $order['status'] }}</span>
        </div>
        <div class="ut-row" style="gap:10px;margin-bottom:24px;background:var(--bg);border-radius:14px;padding:14px 16px">
            <span style="color:var(--blue)"><x-frontend.icon n="truck" :size="20" /></span>
            <span style="font-size:14.5px">{{ $order['status'] === 'Delivered' ? 'Delivered · ' : 'Estimated arrival · ' }}<b>{{ $order['eta'] }}</b></span>
        </div>

        {{-- vertical timeline --}}
        <div style="position:relative;padding-left:6px">
            @foreach($steps as $i => [$label, $desc, $date, $ic])
                @php $done = $i < $order['stage']; $current = $i === $order['stage'] - 1; @endphp
                <div class="ut-row" style="gap:18px;align-items:flex-start;{{ !$loop->last ? 'padding-bottom:28px' : '' }};position:relative">
                    @if(!$loop->last)<div style="position:absolute;left:20px;top:40px;bottom:0;width:2px;background:{{ $done ? 'var(--success)' : 'var(--border)' }}"></div>@endif
                    <span style="width:42px;height:42px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;z-index:1;background:{{ $done ? 'var(--success)' : '#fff' }};color:{{ $done ? '#fff' : 'var(--text-3)' }};border:{{ $done ? 'none' : '2px solid var(--border)' }};{{ $current ? 'box-shadow:0 0 0 5px #dcfce7' : '' }}"><x-frontend.icon :n="$ic" :size="20" /></span>
                    <div style="padding-top:3px">
                        <div class="ut-row" style="gap:10px;flex-wrap:wrap"><span style="font-family:var(--font-head);font-weight:700;font-size:15.5px;color:{{ $done ? 'var(--ink)' : 'var(--text-2)' }}">{{ $label }}</span>@if($current)<span class="ut-tag ut-tag-new" style="padding:2px 9px">Current</span>@endif</div>
                        <p class="muted" style="font-size:13.5px;margin:4px 0 0">{{ $desc }}</p>
                        @if($date)<span style="font-size:12.5px;color:var(--text-3);font-family:var(--font-head);font-weight:600">{{ $date }}</span>@endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="ut-row" style="gap:12px"><a href="{{ route('frontend.pages.contact') }}" class="ut-btn ut-btn-ghost ut-btn-lg">Need help?</a><a href="{{ route('frontend.account.orders.show', $order['id']) }}" class="ut-btn ut-btn-ink ut-btn-lg">View order details</a></div>
</div>
@endsection


