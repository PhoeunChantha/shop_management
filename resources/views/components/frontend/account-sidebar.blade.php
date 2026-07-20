@props(['active' => 'dashboard'])
@php
    $account = app(\App\Services\FrontendAccountService::class);
    $u = $account->user();
    $notifUnread = $account->unreadNotifications();
    $nav = [
        ['dashboard', 'Dashboard', 'home', route('frontend.account.dashboard')],
        ['orders', 'Orders', 'box', route('frontend.account.orders')],
        ['wishlist', 'Wishlist', 'heart', route('frontend.account.wishlist')],
        ['addresses', 'Addresses', 'pin', route('frontend.account.addresses')],
        ['notifications', 'Notifications', 'bell', route('frontend.account.notifications')],
        ['profile', 'Profile', 'user', route('frontend.account.profile')],
        ['password', 'Password', 'lock', route('frontend.account.password')],
    ];
@endphp
<aside class="ut-card ut-acct-side ut-hide-mobile" style="padding:10px;position:sticky;top:96px">
    @foreach($nav as [$key, $label, $ic, $href])
        <a href="{{ $href }}" class="ut-side-link {{ $active === $key ? 'active' : '' }}">
            <x-frontend.icon :n="$ic" :size="19" /> {{ $label }}
            @if($key === 'notifications' && $notifUnread > 0)
                <span class="ut-tag ut-tag-sale" style="margin-left:auto">{{ $notifUnread }}</span>
            @endif
            @if($key === 'wishlist')
                <span class="ut-tag ut-tag-soft" data-wish-count style="margin-left:auto;display:none">0</span>
            @endif
        </a>
    @endforeach
    <hr class="divider" style="margin:8px 6px">
    <a href="{{ route('frontend.login') }}" class="ut-side-link"><x-frontend.icon n="arrowL" :size="18" /> Sign out</a>
</aside>

