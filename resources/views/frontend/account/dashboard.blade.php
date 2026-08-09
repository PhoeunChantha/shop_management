@extends('frontend.account.partials.shell', ['active' => 'dashboard'])
@section('title', __('Account').' — T-Shirt Shop')

@php $activeProduct = $active && !empty($active['items']) ? ($products[$active['items'][0]['pid']] ?? null) : null; @endphp

@section('account')
<div class="ut-row" style="justify-content:space-between;align-items:flex-end;margin-bottom:18px;gap:12px;flex-wrap:wrap">
    <div><h2 style="font-size:24px">{{ __('Welcome back') }}, {{ $user['first'] }}</h2><p class="muted" style="font-size:14px;margin-top:4px">{{ __("Here's what's happening with your account.") }}</p></div>
</div>

{{-- stat cards --}}
<div class="ut-stat-grid" style="margin-bottom:24px">
    @php
        $stats = [
            ['box', count($orders), __('Total orders'), route('frontend.account.orders')],
            ['heart', '—', __('Wishlist items'), route('frontend.account.wishlist'), 'wish'],
            ['spark', number_format($user['points']), __('Thread points'), null],
            ['bell', $notifUnread, __('New alerts'), route('frontend.account.notifications')],
        ];
    @endphp
    @foreach($stats as $s)
        <a href="{{ $s[3] ?? '#' }}" class="ut-card" style="padding:18px;text-align:left;display:block">
            <span style="width:40px;height:40px;border-radius:12px;background:var(--bg);display:grid;place-items:center;color:var(--blue);margin-bottom:12px"><x-frontend.icon :n="$s[0]" :size="20" /></span>
            <div style="font-family:var(--font-head);font-weight:700;font-size:26px" @if(($s[4] ?? null) === 'wish') data-wish-count @endif>{{ $s[1] }}</div>
            <div class="muted" style="font-size:13px">{{ $s[2] }}</div>
        </a>
    @endforeach
</div>

{{-- active order --}}
<div class="ut-card" style="padding:22px;margin-bottom:24px">
    @if($active)
    <div class="ut-row" style="justify-content:space-between;margin-bottom:16px"><h3 style="font-size:17px">{{ __('Latest order') }}</h3><span class="ut-tag {{ $active['status'] === 'Delivered' ? 'ut-tag-success' : 'ut-tag-new' }}">{{ $active['status'] }}</span></div>
    @include('frontend.account.partials.mini-timeline', ['stage' => $active['stage']])
    <div class="ut-row" style="justify-content:space-between;margin-top:18px;flex-wrap:wrap;gap:12px">
        <div class="ut-row" style="gap:12px">
            <x-frontend.ph :tint="$activeProduct['tint'] ?? ''" style="width:52px;height:52px;border-radius:12px" />
            <div><div style="font-family:var(--font-head);font-weight:700">{{ __('Order') }} #UT-{{ $active['id'] }}</div><div class="muted" style="font-size:13px">{{ $active['date'] }} · {{ count($active['items']) }} {{ __('items') }} · {{ money($active['total']) }}</div></div>
        </div>
        <div class="ut-row" style="gap:10px">
            <a href="{{ route('frontend.account.orders.show', $active['id']) }}" class="ut-btn ut-btn-ghost ut-btn-sm">{{ __('Details') }}</a>
            <a href="{{ route('frontend.account.orders.tracking', $active['id']) }}" class="ut-btn ut-btn-ink ut-btn-sm"><x-frontend.icon n="truck" :size="15" /> {{ __('Track') }}</a>
        </div>
    </div>
    @else
        <div style="text-align:center;padding:24px">
            <h3 style="font-size:17px">{{ __('No orders yet') }}</h3>
            <p class="muted" style="font-size:14px;margin:6px 0 16px">{{ __('Your latest order will appear here after checkout.') }}</p>
            <a href="{{ route('frontend.shop.index') }}" class="ut-btn ut-btn-ink ut-btn-sm">{{ __('Start shopping') }}</a>
        </div>
    @endif
</div>

{{-- quick links --}}
<div class="ut-stat-grid" style="grid-template-columns:repeat(3,1fr)">
    @foreach([['pin', __('Address book'), __('Saved addresses'), route('frontend.account.addresses')], ['user', __('Edit profile'), __('Personal info'), route('frontend.account.profile')], ['lock', __('Security'), __('Change password'), route('frontend.account.password')]] as [$ic, $t, $s, $href])
        <a href="{{ $href }}" class="ut-card" style="padding:18px;display:flex;align-items:center;gap:14px">
            <span style="width:44px;height:44px;border-radius:12px;background:var(--bg);display:grid;place-items:center;color:var(--ink)"><x-frontend.icon :n="$ic" :size="20" /></span>
            <div><div style="font-family:var(--font-head);font-weight:600;font-size:14.5px">{{ $t }}</div><div class="muted" style="font-size:12.5px">{{ $s }}</div></div>
            <x-frontend.icon n="chevR" :size="18" style="margin-left:auto;color:var(--text-3)" />
        </a>
    @endforeach
</div>
@endsection


