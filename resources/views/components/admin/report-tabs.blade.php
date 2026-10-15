@props([
    // [['label' => ..., 'route' => ..., 'params' => [], 'active' => [route patterns], 'permission' => ?], ...]
    'tabs' => [],
])

@php
    $visible = collect($tabs)->filter(fn ($tab) => empty($tab['permission']) || auth()->user()?->can($tab['permission']));
    $hasTabs = $visible->count() > 1;
@endphp

@if ($hasTabs || isset($actions))
    {{-- Tabs on the left, page actions (export) on the right. Tabs that stay on
         the same page swap the content in place (data-ajax-link); tabs that
         lead to another report are ordinary navigations. --}}
    <div class="report-tabs-bar">
        @if ($hasTabs)
            <nav class="report-tabs" aria-label="{{ __('Reports in this section') }}" data-report-tabs>
                @foreach ($visible as $tab)
                    @php
                        $isActive = request()->routeIs(...($tab['active'] ?? [$tab['route']]))
                            && collect($tab['params'] ?? [])->every(fn ($value, $key) => (string) request()->query($key, $tab['default'][$key] ?? '') === (string) $value);
                        // Keep the user's filters when switching tabs: everything on the
                        // same page, only the date window across pages. Table state
                        // (page, sort, search) is per-tab and resets.
                        $carry = request()->routeIs($tab['route'])
                            ? request()->except(['page', 'sort', 'direction', 'search', 'view', 'format'])
                            : request()->only(['preset', 'start_date', 'end_date']);
                    @endphp
                    <a href="{{ route($tab['route'], array_merge($carry, $tab['params'] ?? [])) }}"
                        @class(['report-tabs__link', 'is-active' => $isActive])
                        data-ajax-link
                        @if ($isActive) aria-current="page" @endif>
                        @isset($tab['icon'])<i class="fa-solid {{ $tab['icon'] }}"></i>@endisset
                        <span>{{ $tab['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        @endif

        @isset($actions)
            <div class="report-tabs-bar__actions">{{ $actions }}</div>
        @endisset
    </div>
@endif

@once
    <script>
        // Highlight the clicked tab straight away while its content loads.
        document.addEventListener('click', (event) => {
            const link = event.target.closest('[data-report-tabs] .report-tabs__link');
            if (!link || event.metaKey || event.ctrlKey || event.shiftKey) return;
            link.parentElement.querySelectorAll('.report-tabs__link').forEach((el) => {
                el.classList.toggle('is-active', el === link);
                el.toggleAttribute('aria-current', el === link);
            });
        });
    </script>
@endonce
