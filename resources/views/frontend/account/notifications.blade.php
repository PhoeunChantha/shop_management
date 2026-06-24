@extends('frontend.account.partials.shell', ['active' => 'notifications'])
@section('title', 'Notifications — T-Shirt Shop')

@section('account')
<div class="ut-row" style="justify-content:space-between;align-items:flex-end;margin-bottom:18px;gap:12px;flex-wrap:wrap">
    <div><h2 style="font-size:24px">Notifications</h2><p class="muted" style="font-size:14px;margin-top:4px">Order updates, drops, and account activity</p></div>
    <button type="button" class="ut-btn ut-btn-ghost ut-btn-sm" onclick="document.querySelectorAll('.notif-dot').forEach(d=>d.remove()); document.querySelectorAll('.notif-row').forEach(r=>r.style.background='transparent'); utToast('All caught up!')"><x-frontend.icon n="check" :size="15" /> Mark all read</button>
</div>
<div class="ut-card" style="padding:6px">
    @foreach($notifications as $n)
        @php
            $iconBg = $n['type'] === 'promo' ? '#fde9d9' : 'var(--bg)';
            $iconColor = $n['type'] === 'promo' ? 'var(--orange-hover)' : ($n['type'] === 'order' ? 'var(--blue)' : 'var(--ink)');
        @endphp
        <div class="notif-row" style="display:flex;gap:14px;padding:16px;{{ !$loop->last ? 'border-bottom:1px solid var(--border-2)' : '' }};background:{{ $n['unread'] ? '#f0f6ff' : 'transparent' }};border-radius:12px">
            <span style="width:42px;height:42px;border-radius:12px;flex-shrink:0;display:grid;place-items:center;background:{{ $iconBg }};color:{{ $iconColor }}"><x-frontend.icon :n="$n['icon']" :size="20" /></span>
            <div style="flex:1">
                <div class="ut-row" style="justify-content:space-between;gap:10px">
                    <span style="font-family:var(--font-head);font-weight:600;font-size:14.5px">{{ $n['title'] }}</span>
                    @if($n['unread'])<span class="notif-dot" style="width:8px;height:8px;border-radius:8px;background:var(--blue);flex-shrink:0;margin-top:6px"></span>@endif
                </div>
                <p class="muted" style="font-size:13.5px;margin:3px 0 0;line-height:1.5">{{ $n['body'] }}</p>
                <span style="font-size:12px;color:var(--text-3);font-family:var(--font-head);font-weight:500">{{ $n['time'] }}</span>
            </div>
        </div>
    @endforeach
</div>
@endsection

