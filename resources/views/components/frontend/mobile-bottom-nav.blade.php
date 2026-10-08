@php
    $cur = Route::currentRouteName() ?? '';
    // Tabs inside the glass dock; the bag is a separate floating button.
    $tabs = [
        ['href' => route('frontend.home'), 'icon' => 'home', 'label' => __('Home'), 'active' => in_array($cur, ['home', 'frontend.home'], true)],
        ['href' => route('frontend.shop.index'), 'icon' => 'grid', 'label' => __('Shop'), 'active' => str_starts_with($cur, 'frontend.shop')],
        ['href' => route('frontend.pages.faq'), 'icon' => 'info', 'label' => __('Help'), 'active' => $cur === 'frontend.pages.faq'],
        ['drawer' => '#profileDrawer', 'icon' => 'user', 'label' => __('Account')],
    ];
    $activeIndex = collect($tabs)->search(fn ($tab) => ! empty($tab['active']));
@endphp

<nav class="ut-bottomnav" aria-label="{{ __('Main') }}" data-bottomnav>
    <div @class(['ut-dock', 'is-idle' => $activeIndex === false]) style="--i: {{ $activeIndex === false ? 0 : $activeIndex }}; --n: {{ count($tabs) }};" data-dock>
        {{-- Sliding lens behind the active tab. --}}
        <span class="ut-dock__lens" aria-hidden="true"></span>

        @foreach ($tabs as $index => $tab)
            @if (! empty($tab['drawer']))
                <a href="#" role="button" class="ut-dock__tab" data-bs-toggle="offcanvas" data-bs-target="{{ $tab['drawer'] }}">
                    <span class="ut-dock__icon">
                        <x-frontend.icon :n="$tab['icon']" :size="21" />
                        <span class="ut-badge accent ut-dock__badge" data-wish-count style="display:none">0</span>
                    </span>
                    <span class="ut-dock__label">{{ $tab['label'] }}</span>
                </a>
            @else
                <a href="{{ $tab['href'] }}" @class(['ut-dock__tab', 'active' => $tab['active']]) @if ($tab['active']) aria-current="page" @endif
                    data-dock-link="{{ $index }}">
                    <span class="ut-dock__icon"><x-frontend.icon :n="$tab['icon']" :size="21" /></span>
                    <span class="ut-dock__label">{{ $tab['label'] }}</span>
                </a>
            @endif
        @endforeach
    </div>

    <a href="#" role="button" class="ut-dock__bag" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer" aria-label="{{ __('Bag') }}">
        <x-frontend.icon n="bag" :size="22" />
        <span class="ut-badge accent ut-dock__badge" data-cart-count style="display:none">0</span>
    </a>
</nav>

@once
    <script>
        // Slide the lens the moment a tab is tapped, so it glides to the new
        // tab while the next page loads instead of hard-cutting after.
        document.addEventListener('click', function (e) {
            var link = e.target.closest('[data-dock-link]');
            if (!link || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.button > 0) return;
            var dock = link.closest('[data-dock]');
            dock.style.setProperty('--i', link.dataset.dockLink);
            dock.classList.remove('is-idle');
            dock.querySelectorAll('[data-dock-link]').forEach(function (a) {
                a.classList.toggle('active', a === link);
                if (a === link) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
            });
        });
    </script>
@endonce
