@php
    $cur = Route::currentRouteName();
    $nav = $frontendNav ?? [];
    $categoryMenus = $nav['categoryMenus'] ?? [];
    $search = $nav['search'] ?? [];
    $announcements = $nav['announcements'] ?? ['Free shipping over $75', '30-day returns', 'New collection released', 'First order 10% off'];
@endphp
<header class="ut-header">
    <div class="ut-announce" aria-label="Store benefits">
        <div class="ut-announce-track">
            @foreach(array_merge($announcements, $announcements, $announcements) as $message)
                <span>{{ $message }}<i aria-hidden="true">✦</i></span>
            @endforeach
        </div>
    </div>

    <div class="ut-wrap ut-header-main">
        <button class="ut-menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#utMobileMenu" aria-label="Open menu">
            <x-frontend.icon n="menu" :size="22" />
        </button>

        <a href="{{ route('frontend.home') }}" class="ut-brand" aria-label="T-Shirt Shop home">
            <span class="ut-logo-mark">T</span>
            <span class="ut-logo-text">T SHIRT SHOP</span>
        </a>

        <nav class="ut-nav ut-desktop-nav" aria-label="Primary navigation">
            @foreach($categoryMenus as $menu)
                <div class="ut-mega-wrap">
                    <a href="{{ $menu['url'] }}" class="ut-nav-trigger {{ str_starts_with($cur ?? '', 'frontend.shop') && $loop->first ? 'active' : '' }}">{{ $menu['label'] }} <x-frontend.icon n="chevD" :size="13" /></a>
                    <div class="ut-mega-menu ut-mega-menu-compact">
                        <div class="ut-mega-links"><span class="ut-mega-label">{{ __('Shop sub-categories') }}</span>@foreach($menu['children'] ?? [] as $item)<a href="{{ $item['url'] }}">{{ $item['label'] }} <x-frontend.icon n="arrowR" :size="14" /></a>@endforeach</div>
                        <a href="{{ $menu['url'] }}" class="ut-mega-feature {{ $menu['feature_class'] ?? 'ut-mega-collection' }}"><span>{!! nl2br(e($menu['feature_title'])) !!}</span><small>{{ $menu['feature_cta'] ?? __('Shop category') }} <x-frontend.icon n="arrowR" :size="13" /></small></a>
                    </div>
                </div>
            @endforeach
        </nav>

        <div class="ut-header-actions">
            <button type="button" class="ut-header-search" data-bs-toggle="modal" data-bs-target="#utSearchOverlay" aria-label="Search the shop">
                <x-frontend.icon n="search" :size="18" /><span>{{ __('Search the collection') }}</span><kbd>⌘ K</kbd>
            </button>
            @php
                $locales = [
                    'en' => ['label' => 'EN', 'name' => 'EN', 'flag' => 'gb'],
                    'km' => ['label' => 'KH', 'name' => 'KH', 'flag' => 'kh'],
                ];
                $curLocale = $locales[app()->getLocale()] ?? $locales['en'];
            @endphp
            <div class="dropdown ut-lang-dropdown">
                <button class="ut-lang-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Language') }}">
                    <img class="ut-lang-flag" src="{{ asset('assets/frontend/img/flags/'.$curLocale['flag'].'.svg') }}" alt="" width="20" height="15" loading="lazy">
                    <span class="ut-lang-btn__code">{{ $curLocale['label'] }}</span>
                    <span class="ut-lang-btn__caret"><x-frontend.icon n="chevD" :size="11" /></span>
                </button>
                <div class="dropdown-menu dropdown-menu-end ut-account-menu ut-lang-menu">
                    @foreach($locales as $code => $loc)
                        <a href="{{ route('lang.switch', $code) }}" @class(['is-active' => app()->getLocale() === $code])>
                            <span class="ut-lang-opt"><img class="ut-lang-flag" src="{{ asset('assets/frontend/img/flags/'.$loc['flag'].'.svg') }}" alt="" width="20" height="15" loading="lazy"> {{ $loc['name'] }}</span>
                            @if(app()->getLocale() === $code)<x-frontend.icon n="check" :size="15" />@endif
                        </a>
                    @endforeach
                </div>
            </div>
            @php($displayCurrencies = app(\App\Services\Admin\SettingService::class)->displayCurrencies())
            @if(count($displayCurrencies) > 1)
                @php($curDc = app(\App\Services\Admin\SettingService::class)->displayCurrency())
                <div class="dropdown ut-lang-dropdown ut-hide-mobile">
                    <button class="ut-lang-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Currency') }}">
                        <span class="ut-lang-btn__code">{{ $curDc['code'] }}</span>
                        <span class="ut-lang-btn__caret"><x-frontend.icon n="chevD" :size="11" /></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end ut-account-menu ut-lang-menu">
                        @foreach($displayCurrencies as $code => $dc)
                            <a href="{{ route('currency.switch', $code) }}" @class(['is-active' => $curDc['code'] === $code])>
                                <span class="ut-lang-opt">{{ $dc['symbol'] }} {{ $code }}</span>
                                @if($curDc['code'] === $code)<x-frontend.icon n="check" :size="15" />@endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="dropdown ut-account-dropdown">
                <button class="icon-btn ut-header-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Account') }}"><x-frontend.icon n="user" :size="19" /></button>
                <div class="dropdown-menu dropdown-menu-end ut-account-menu">
                    @auth
                        <div class="ut-account-menu-title">{{ __('Your Urban Thread') }}</div>
                        <a href="{{ route('frontend.account.dashboard') }}">{{ __('My account') }}</a><a href="{{ route('frontend.account.orders') }}">{{ __('Orders & tracking') }}</a><a href="{{ route('frontend.account.wishlist') }}">{{ __('Saved pieces') }}</a>
                        <form method="POST" action="{{ route('frontend.logout') }}" class="d-none">@csrf</form>
                        <a href="{{ route('frontend.logout') }}" onclick="event.preventDefault(); this.closest('.ut-account-menu').querySelector('form').submit();">{{ __('Sign out') }}</a>
                    @else
                        <div class="ut-account-menu-title">{{ __('Welcome') }}</div>
                        <a href="{{ route('frontend.login') }}">{{ __('Sign in') }}</a><a href="{{ route('frontend.register') }}">{{ __('Create account') }}</a>
                    @endauth
                </div>
            </div>
            @auth
                @php($notifUnread = auth()->user()->unreadNotifications()->count())
                <a href="{{ route('frontend.account.notifications') }}" class="icon-btn ut-header-icon ut-hide-mobile" title="Notifications"><x-frontend.icon n="bell" :size="19" /><span class="ut-badge accent" data-notif-count style="{{ $notifUnread > 0 ? '' : 'display:none' }}">{{ $notifUnread }}</span></a>
            @endauth
            <a href="{{ route('frontend.account.wishlist') }}" class="icon-btn ut-header-icon ut-hide-mobile" title="Wishlist"><x-frontend.icon n="heart" :size="19" /><span class="ut-badge accent" data-wish-count style="display:none">0</span></a>
            <button type="button" class="icon-btn ut-cart-button" title="Bag" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer"><x-frontend.icon n="bag" :size="19" /><span class="ut-badge" data-cart-count style="display:none">0</span></button>
        </div>
    </div>
</header>

<div class="offcanvas offcanvas-start ut-mobile-menu" tabindex="-1" id="utMobileMenu" aria-labelledby="utMobileMenuLabel">
    <div class="offcanvas-header"><a href="{{ route('frontend.home') }}" class="ut-brand" id="utMobileMenuLabel"><span class="ut-logo-mark">T</span><span class="ut-logo-text">T SHIRT SHOP</span></a><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
    <div class="offcanvas-body"><button type="button" class="ut-mobile-search" data-bs-dismiss="offcanvas" data-bs-toggle="modal" data-bs-target="#utSearchOverlay"><x-frontend.icon n="search" :size="18" /> {{ __('Search the collection') }}</button><nav>@foreach($nav['mobile'] ?? [] as $item)<a href="{{ $item['url'] }}">{{ $item['label'] }} <x-frontend.icon n="arrowR" :size="18" /></a>@endforeach</nav><div class="ut-mobile-menu-foot"><span>{{ __('FIRST ORDER') }}</span><strong>{{ __('10% OFF') }}</strong><p>{{ __('Sign up for early drops and exclusive editions.') }}</p></div></div>
</div>

<div class="modal fade ut-search-modal" id="utSearchOverlay" tabindex="-1" aria-labelledby="utSearchOverlayLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-body"><div class="ut-search-top"><label id="utSearchOverlayLabel" class="visually-hidden" for="utSearchInput">{{ __('Search products') }}</label><x-frontend.icon n="search" :size="23" /><input id="utSearchInput" type="search" placeholder="{{ __('What are you looking for?') }}" autofocus><button type="button" data-bs-dismiss="modal">{{ __('Esc') }}</button></div><div class="ut-search-content"><div><span>{{ __('Recent searches') }}</span><div class="ut-search-chips">@foreach($search['recent'] ?? [] as $item)<a href="{{ $item['url'] }}">{{ $item['label'] }}</a>@endforeach</div></div><div><span>{{ __('Trending now') }}</span><div class="ut-search-chips">@foreach($search['trending'] ?? [] as $item)<a href="{{ $item['url'] }}">{{ $item['label'] }}</a>@endforeach</div></div><div class="ut-search-suggestions"><span>{{ __('Suggested categories') }}</span>@foreach($search['categories'] ?? [] as $item)<a href="{{ $item['url'] }}">{{ $item['label'] }} <x-frontend.icon n="arrowR" :size="15" /></a>@endforeach</div><div class="ut-search-products"><span>{{ __('Popular products') }}</span>@foreach($search['products'] ?? [] as $index => $item)<a href="{{ $item['url'] }}"><i class="ut-search-product-image {{ $index === 1 ? 'second' : '' }}"></i><b>{{ $item['label'] }}</b><small>{{ $item['price'] ?? '' }}</small></a>@endforeach</div></div></div></div></div></div>
