@php $cur = Route::currentRouteName(); @endphp
<nav class="ut-bottomnav">
    <a href="{{ route('frontend.home') }}" class="{{ $cur === 'home' ? 'active' : '' }}">
        <span style="position:relative"><x-frontend.icon n="home" :size="22" /></span>
        <span>Home</span>
    </a>
    <a href="{{ route('frontend.shop.index') }}" class="{{ str_starts_with($cur ?? '', 'frontend.shop') ? 'active' : '' }}">
        <span style="position:relative"><x-frontend.icon n="grid" :size="22" /></span>
        <span>Shop</span>
    </a>
    {{-- floating cart button --}}
    <a href="#" role="button" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer">
        <span style="position:relative">
            <span class="fab"><x-frontend.icon n="bag" :size="20" /></span>
            <span class="ut-badge accent" data-cart-count style="display:none;top:-24px;right:-6px">0</span>
        </span>
        <span>Bag</span>
    </a>
    <a href="{{ route('frontend.pages.faq') }}" class="{{ $cur === 'frontend.pages.faq' ? 'active' : '' }}">
        <span style="position:relative"><x-frontend.icon n="info" :size="22" /></span>
        <span>Help</span>
    </a>
    <a href="#" role="button" data-bs-toggle="offcanvas" data-bs-target="#profileDrawer">
        <span style="position:relative">
            <x-frontend.icon n="user" :size="22" />
            <span class="ut-badge accent" data-wish-count style="display:none">0</span>
        </span>
        <span>Account</span>
    </a>
</nav>

