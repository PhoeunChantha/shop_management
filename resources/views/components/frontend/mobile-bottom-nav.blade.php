@php
    $cur = Route::currentRouteName() ?? '';
    $tabs = [
        ['href' => route('frontend.home'), 'icon' => 'home', 'label' => __('Home'), 'active' => $cur === 'home' || $cur === 'frontend.home'],
        ['href' => route('frontend.shop.index'), 'icon' => 'grid', 'label' => __('Shop'), 'active' => str_starts_with($cur, 'frontend.shop')],
        ['bag' => true],
        ['href' => route('frontend.pages.faq'), 'icon' => 'info', 'label' => __('Help'), 'active' => $cur === 'frontend.pages.faq'],
        ['drawer' => '#profileDrawer', 'icon' => 'user', 'label' => __('Account'), 'badge' => 'data-wish-count'],
    ];
@endphp

<nav class="ut-bottomnav" aria-label="{{ __('Main') }}" data-bottomnav>
    @foreach ($tabs as $tab)
        @if (! empty($tab['bag']))
            {{-- Floating bag button opens the cart drawer. --}}
            <a href="#" role="button" class="ut-bottomnav__bag" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer" aria-label="{{ __('Bag') }}">
                <span class="ut-bottomnav__fab">
                    <x-frontend.icon n="bag" :size="20" />
                    <span class="ut-badge accent ut-bottomnav__badge" data-cart-count style="display:none">0</span>
                </span>
                <span class="ut-bottomnav__label">{{ __('Bag') }}</span>
            </a>
        @elseif (! empty($tab['drawer']))
            <a href="#" role="button" data-bs-toggle="offcanvas" data-bs-target="{{ $tab['drawer'] }}">
                <span class="ut-bottomnav__icon">
                    <x-frontend.icon :n="$tab['icon']" :size="22" />
                    <span class="ut-badge accent ut-bottomnav__badge" {{ $tab['badge'] }} style="display:none">0</span>
                </span>
                <span class="ut-bottomnav__label">{{ $tab['label'] }}</span>
            </a>
        @else
            <a href="{{ $tab['href'] }}" @class(['active' => $tab['active']]) @if ($tab['active']) aria-current="page" @endif data-bottomnav-link>
                <span class="ut-bottomnav__icon"><x-frontend.icon :n="$tab['icon']" :size="22" /></span>
                <span class="ut-bottomnav__label">{{ $tab['label'] }}</span>
            </a>
        @endif
    @endforeach
</nav>

@once
    <script>
        // Move the active state the moment a tab is tapped, so the pill
        // animates while the next page loads instead of hard-cutting after.
        document.addEventListener('click', function (e) {
            var link = e.target.closest('[data-bottomnav-link]');
            if (!link || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.button > 0) return;
            var nav = link.closest('[data-bottomnav]');
            nav.querySelectorAll('[data-bottomnav-link]').forEach(function (a) {
                a.classList.toggle('active', a === link);
                if (a === link) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
            });
        });
    </script>
@endonce
