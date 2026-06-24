@php $cur = Route::currentRouteName(); @endphp
<header class="ut-header">
    <div class="ut-announce" aria-label="Store benefits">
        <div class="ut-announce-track">
            @foreach(array_merge(['Free shipping over $75', '30-day returns', 'New collection released', 'First order 10% off'], ['Free shipping over $75', '30-day returns', 'New collection released', 'First order 10% off'], ['Free shipping over $75', '30-day returns', 'New collection released', 'First order 10% off']) as $message)
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
            <div class="ut-mega-wrap">
                <a href="{{ route('frontend.shop.index') }}" class="ut-nav-trigger {{ str_starts_with($cur ?? '', 'frontend.shop') ? 'active' : '' }}">New in <x-frontend.icon n="chevD" :size="13" /></a>
                <div class="ut-mega-menu ut-mega-menu-compact">
                    <div class="ut-mega-links"><span class="ut-mega-label">Fresh this week</span>@foreach(['New arrivals', 'Latest drops', 'Trending now', 'Staff picks'] as $item)<a href="{{ route('frontend.shop.index') }}">{{ $item }} <x-frontend.icon n="arrowR" :size="14" /></a>@endforeach</div>
                    <a href="{{ route('frontend.shop.index') }}" class="ut-mega-feature ut-mega-collection"><span>FRESH<br>OFF THE PRESS</span><small>Shop new <x-frontend.icon n="arrowR" :size="13" /></small></a>
                </div>
            </div>
            <div class="ut-mega-wrap">
                <a href="{{ route('frontend.shop.index') }}" class="ut-nav-trigger">Best sellers <x-frontend.icon n="chevD" :size="13" /></a>
                <div class="ut-mega-menu ut-mega-menu-compact">
                    <div class="ut-mega-links"><span class="ut-mega-label">Most wanted</span>@foreach(['Most loved tees', 'Heavyweight favorites', 'Community picks', 'Sale best sellers'] as $item)<a href="{{ route('frontend.shop.index') }}">{{ $item }} <x-frontend.icon n="arrowR" :size="14" /></a>@endforeach</div>
                    <a href="{{ route('frontend.shop.index') }}" class="ut-mega-feature ut-mega-sale"><span>THE<br>HALL OF FAME</span><small>Shop icons <x-frontend.icon n="arrowR" :size="13" /></small></a>
                </div>
            </div>
            <div class="ut-mega-wrap">
                <a href="{{ route('frontend.shop.index') }}" class="ut-nav-trigger">Tees <x-frontend.icon n="chevD" :size="13" /></a>
                <div class="ut-mega-menu">
                    <div class="ut-mega-links"><span class="ut-mega-label">Fits with presence</span>@foreach(['Heavyweight', 'Vintage wash', 'Streetwear', 'Minimal'] as $item)<a href="{{ route('frontend.shop.index') }}">{{ $item }} <x-frontend.icon n="arrowR" :size="14" /></a>@endforeach</div>
                    <a href="{{ route('frontend.shop.index') }}" class="ut-mega-feature ut-mega-oversized"><span>THE WEIGHT<br>YOU FEEL</span><small>Explore oversized <x-frontend.icon n="arrowR" :size="13" /></small></a>
                    <div class="ut-mega-colors"><span class="ut-mega-label">Shop by color</span><div><i style="background:#191919"></i><i style="background:#f3eee6"></i><i style="background:#7c836d"></i><i style="background:#bc5635"></i><i style="background:#65788b"></i></div><small>Core pigment range</small></div>
                </div>
            </div>
            <div class="ut-mega-wrap">
                <a href="{{ route('frontend.shop.index') }}" class="ut-nav-trigger">Graphics <x-frontend.icon n="chevD" :size="13" /></a>
                <div class="ut-mega-menu ut-mega-menu-compact">
                    <div class="ut-mega-links"><span class="ut-mega-label">Wear your point of view</span>@foreach(['Anime', 'Typography', 'Street art', 'Limited edition'] as $item)<a href="{{ route('frontend.shop.index') }}">{{ $item }} <x-frontend.icon n="arrowR" :size="14" /></a>@endforeach</div>
                    <a href="{{ route('frontend.shop.index') }}" class="ut-mega-feature ut-mega-graphic"><span>GRAPHIC<br>LANGUAGE</span><small>Shop prints <x-frontend.icon n="arrowR" :size="13" /></small></a>
                </div>
            </div>
            <div class="ut-mega-wrap">
                <a href="{{ route('frontend.shop.index') }}" class="ut-nav-trigger">Collections <x-frontend.icon n="chevD" :size="13" /></a>
                <div class="ut-mega-menu ut-mega-menu-compact">
                    <div class="ut-mega-links"><span class="ut-mega-label">Built around a mood</span>@foreach(['Summer collection', 'Urban collection', 'Minimal collection', 'Sale up to 40% off'] as $item)<a href="{{ route('frontend.shop.index') }}">{{ $item }} <x-frontend.icon n="arrowR" :size="14" /></a>@endforeach</div>
                    <a href="{{ route('frontend.shop.index') }}" class="ut-mega-feature ut-mega-collection"><span>CURATED<br>UNIFORMS</span><small>View all collections <x-frontend.icon n="arrowR" :size="13" /></small></a>
                </div>
            </div>
        </nav>

        <div class="ut-header-actions">
            <button type="button" class="ut-header-search" data-bs-toggle="modal" data-bs-target="#utSearchOverlay" aria-label="Search the shop">
                <x-frontend.icon n="search" :size="18" /><span>Search the collection</span><kbd>⌘ K</kbd>
            </button>
            <div class="dropdown ut-account-dropdown">
                <button class="icon-btn ut-header-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account"><x-frontend.icon n="user" :size="19" /></button>
                <div class="dropdown-menu dropdown-menu-end ut-account-menu">
                    <div class="ut-account-menu-title">Your Urban Thread</div>
                    <a href="{{ route('frontend.account.dashboard') }}">My account</a><a href="{{ route('frontend.account.orders') }}">Orders & tracking</a><a href="{{ route('frontend.account.wishlist') }}">Saved pieces</a>
                </div>
            </div>
            <a href="{{ route('frontend.account.wishlist') }}" class="icon-btn ut-header-icon ut-hide-mobile" title="Wishlist"><x-frontend.icon n="heart" :size="19" /><span class="ut-badge accent" data-wish-count style="display:none">0</span></a>
            <button type="button" class="icon-btn ut-cart-button" title="Bag" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer"><x-frontend.icon n="bag" :size="19" /><span class="ut-badge" data-cart-count style="display:none">0</span></button>
        </div>
    </div>
</header>

<div class="offcanvas offcanvas-start ut-mobile-menu" tabindex="-1" id="utMobileMenu" aria-labelledby="utMobileMenuLabel">
    <div class="offcanvas-header"><a href="{{ route('frontend.home') }}" class="ut-brand" id="utMobileMenuLabel"><span class="ut-logo-mark">T</span><span class="ut-logo-text">T SHIRT SHOP</span></a><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
    <div class="offcanvas-body"><button type="button" class="ut-mobile-search" data-bs-dismiss="offcanvas" data-bs-toggle="modal" data-bs-target="#utSearchOverlay"><x-frontend.icon n="search" :size="18" /> Search the collection</button><nav>@foreach(['New in', 'Best sellers', 'Tees', 'Graphics', 'Collections'] as $item)<a href="{{ route('frontend.shop.index') }}">{{ $item }} <x-frontend.icon n="arrowR" :size="18" /></a>@endforeach</nav><div class="ut-mobile-menu-foot"><span>FIRST ORDER</span><strong>10% OFF</strong><p>Sign up for early drops and exclusive editions.</p></div></div>
</div>

<div class="modal fade ut-search-modal" id="utSearchOverlay" tabindex="-1" aria-labelledby="utSearchOverlayLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-body"><div class="ut-search-top"><label id="utSearchOverlayLabel" class="visually-hidden" for="utSearchInput">Search products</label><x-frontend.icon n="search" :size="23" /><input id="utSearchInput" type="search" placeholder="What are you looking for?" autofocus><button type="button" data-bs-dismiss="modal">Esc</button></div><div class="ut-search-content"><div><span>Recent searches</span><div class="ut-search-chips"><a href="{{ route('frontend.shop.index') }}">Oversized black tee</a><a href="{{ route('frontend.shop.index') }}">Vintage wash</a></div></div><div><span>Trending now</span><div class="ut-search-chips"><a href="{{ route('frontend.shop.index') }}">Heavyweight</a><a href="{{ route('frontend.shop.index') }}">Summer drop</a><a href="{{ route('frontend.shop.index') }}">Graphic tees</a></div></div><div class="ut-search-suggestions"><span>Suggested categories</span><a href="{{ route('frontend.shop.index') }}">Oversized tees <x-frontend.icon n="arrowR" :size="15" /></a><a href="{{ route('frontend.shop.index') }}">Best sellers <x-frontend.icon n="arrowR" :size="15" /></a><a href="{{ route('frontend.shop.index') }}">Limited graphics <x-frontend.icon n="arrowR" :size="15" /></a></div><div class="ut-search-products"><span>Popular products</span><a href="{{ route('frontend.shop.index') }}"><i class="ut-search-product-image"></i><b>Essential Heavyweight Tee</b><small>$42</small></a><a href="{{ route('frontend.shop.index') }}"><i class="ut-search-product-image second"></i><b>Vintage Box Tee</b><small>$48</small></a></div></div></div></div></div></div>
