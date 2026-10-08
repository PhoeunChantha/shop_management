@php $cur = Route::currentRouteName(); @endphp
<nav class="ut-bottomnav" data-bottomnav>
    <a href="{{ route('frontend.home') }}" class="{{ in_array($cur, ['home', 'frontend.home'], true) ? 'active' : '' }}" data-bottomnav-link>
        <span class="ut-bn-icon"><x-frontend.icon n="home" :size="22" /></span>
        <span>{{ __('Home') }}</span>
    </a>
    <a href="{{ route('frontend.shop.index') }}" class="{{ str_starts_with($cur ?? '', 'frontend.shop') ? 'active' : '' }}" data-bottomnav-link>
        <span class="ut-bn-icon"><x-frontend.icon n="grid" :size="22" /></span>
        <span>{{ __('Shop') }}</span>
    </a>
    {{-- floating cart button --}}
    <a href="#" role="button" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer">
        <span class="ut-bn-icon ut-bn-icon--fab">
            <span class="fab"><x-frontend.icon n="bag" :size="20" /></span>
            <span class="ut-badge accent" data-cart-count style="display:none;top:-24px;right:-6px">0</span>
        </span>
        <span>{{ __('Bag') }}</span>
    </a>
    <a href="{{ route('frontend.pages.faq') }}" class="{{ $cur === 'frontend.pages.faq' ? 'active' : '' }}" data-bottomnav-link>
        <span class="ut-bn-icon"><x-frontend.icon n="info" :size="22" /></span>
        <span>{{ __('Help') }}</span>
    </a>
    <a href="#" role="button" data-bs-toggle="offcanvas" data-bs-target="#profileDrawer">
        <span class="ut-bn-icon">
            <x-frontend.icon n="user" :size="22" />
            <span class="ut-badge accent" data-wish-count style="display:none">0</span>
        </span>
        <span>{{ __('Account') }}</span>
    </a>
</nav>

@once
    <script>
        // Tap feedback for the bottom nav: the icon bounces and a soft ripple
        // fades behind it; a page tab turns active immediately instead of
        // waiting for the next page to load.
        document.addEventListener('click', function (e) {
            var item = e.target.closest('[data-bottomnav] a');
            if (!item || e.metaKey || e.ctrlKey || e.shiftKey || e.button > 0) return;

            var icon = item.querySelector('.ut-bn-icon');
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (icon && !reduce) {
                icon.classList.remove('is-tapped');
                void icon.offsetWidth; // restart the ripple
                icon.classList.add('is-tapped');
                var target = icon.querySelector('.fab') || icon.querySelector('svg');
                if (target && target.animate) {
                    target.animate(
                        [{ transform: 'scale(.82)' }, { transform: 'scale(1.12)' }, { transform: 'scale(1)' }],
                        { duration: 420, easing: 'cubic-bezier(.34, 1.56, .64, 1)' }
                    );
                }
            }

            if (item.hasAttribute('data-bottomnav-link')) {
                item.closest('[data-bottomnav]').querySelectorAll('[data-bottomnav-link]').forEach(function (a) {
                    a.classList.toggle('active', a === item);
                });
            }
        });
    </script>
@endonce
