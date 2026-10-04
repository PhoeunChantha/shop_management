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
            <a href="{{ route($tab['route'], $tab['params'] ?? []) }}"
                @class(['report-tabs__link', 'is-active' => $isActive])
                @if ($isActive) aria-current="page" @endif>
                @isset($tab['icon'])<i class="fa-solid {{ $tab['icon'] }}"></i>@endisset
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </nav>
@endif
