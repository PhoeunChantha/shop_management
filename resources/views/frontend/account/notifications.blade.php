@extends('frontend.account.partials.shell', ['active' => 'notifications'])
@section('title', 'Notifications — T-Shirt Shop')

@section('account')
<div class="ut-row" style="justify-content:space-between;align-items:flex-end;margin-bottom:18px;gap:12px;flex-wrap:wrap">
    <div><h2 style="font-size:24px">Notifications</h2><p class="muted" style="font-size:14px;margin-top:4px">Order updates, drops, and account activity</p></div>
    @if(collect($notifications)->where('unread', true)->count())
        <form method="POST" action="{{ route('frontend.account.notifications.read-all') }}">
            @csrf
            <button type="submit" class="ut-btn ut-btn-ghost ut-btn-sm"><x-frontend.icon n="check" :size="15" /> Mark all read</button>
        </form>
    @endif
</div>

@if(count($notifications))
    <div class="ut-card" style="padding:6px" id="notifList">
        @foreach($notifications as $n)
            @php
                $iconBg = $n['type'] === 'promo' ? '#fde9d9' : 'var(--bg)';
                $iconColor = $n['type'] === 'promo' ? 'var(--orange-hover)' : ($n['type'] === 'order' ? 'var(--blue)' : 'var(--ink)');
            @endphp
            <div class="notif-row" style="display:flex;gap:14px;padding:16px;{{ !$loop->last ? 'border-bottom:1px solid var(--border-2)' : '' }};background:{{ $n['unread'] ? '#f0f6ff' : 'transparent' }};border-radius:12px">
                <span style="width:42px;height:42px;border-radius:12px;flex-shrink:0;display:grid;place-items:center;background:{{ $iconBg }};color:{{ $iconColor }}"><x-frontend.icon :n="$n['icon']" :size="20" /></span>
                <div style="flex:1">
                    <div class="ut-row" style="justify-content:space-between;gap:10px">
                        @if(!empty($n['url']))
                            <a href="{{ $n['url'] }}" style="font-family:var(--font-head);font-weight:600;font-size:14.5px;color:var(--ink)">{{ $n['title'] }}</a>
                        @else
                            <span style="font-family:var(--font-head);font-weight:600;font-size:14.5px">{{ $n['title'] }}</span>
                        @endif
                        @if($n['unread'])<span class="notif-dot" style="width:8px;height:8px;border-radius:8px;background:var(--blue);flex-shrink:0;margin-top:6px"></span>@endif
                    </div>
                    <p class="muted" style="font-size:13.5px;margin:3px 0 0;line-height:1.5">{{ $n['body'] }}</p>
                    <div class="ut-row" style="gap:12px;margin-top:4px">
                        <span style="font-size:12px;color:var(--text-3);font-family:var(--font-head);font-weight:500">{{ $n['time'] }}</span>
                        @if($n['unread'])
                            <form method="POST" action="{{ route('frontend.account.notifications.read', $n['id']) }}">
                                @csrf @method('PATCH')
                                <button type="submit" style="border:0;background:none;color:var(--blue);font-size:12px;font-family:var(--font-head);font-weight:600;cursor:pointer;padding:0">Mark read</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="ut-card" style="padding:56px;text-align:center">
        <div style="width:64px;height:64px;border-radius:20px;background:var(--bg);display:grid;place-items:center;margin:0 auto 16px;color:var(--text-3)"><x-frontend.icon n="bell" :size="28" /></div>
        <h3>No notifications yet</h3><p class="muted" style="margin-top:6px">Order updates and account activity will show up here.</p>
    </div>
@endif
@endsection

