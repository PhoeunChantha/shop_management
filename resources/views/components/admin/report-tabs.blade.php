@props([
    // [['label' => ..., 'route' => ..., 'params' => [], 'active' => [route patterns], 'permission' => ?], ...]
    'tabs' => [],
])

@php
    $visible = collect($tabs)->filter(fn ($tab) => empty($tab['permission']) || auth()->user()?->can($tab['permission']));
@endphp

@if ($visible->count() > 1)
    <nav class="report-tabs" aria-label="{{ __('Reports in this section') }}">
        @foreach ($visible as $tab)
            @php
                $isActive = request()->routeIs(...($tab['active'] ?? [$tab['route']]))
                    && collect($tab['params'] ?? [])->every(fn ($value, $key) => (string) request()->query($key, $tab['default'][$key] ?? '') === (string) $value);
            @endphp
            @php
                // Keep the user's filters when switching tabs: everything on the
                // same page, only the date window across pages. Table state
                // (page, sort, search) is per-tab and resets.
                $carry = request()->routeIs($tab['route'])
                    ? request()->except(['page', 'sort', 'direction', 'search', 'view', 'format'])
                    : request()->only(['preset', 'start_date', 'end_date']);
            @endphp
            <a href="{{ route($tab['route'], array_merge($carry, $tab['params'] ?? [])) }}"
                @class(['report-tabs__link', 'is-active' => $isActive])
                @if ($isActive) aria-current="page" @endif>
                @isset($tab['icon'])<i class="fa-solid {{ $tab['icon'] }}"></i>@endisset
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </nav>
@endif
