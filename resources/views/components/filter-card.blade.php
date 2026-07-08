@props([
    'action',
    'reset' => null,
    'method' => 'GET',
    'grid' => 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3',
    'applyLabel' => 'Apply Filters',
    'resetLabel' => 'Reset',
])

{{--
    Reusable filter card for admin index pages.

    Slots:
      - default : the filter fields (selects / inputs) laid out inside the grid
      - hidden  : extra hidden inputs to preserve (e.g. search / per_page)
      - actions : extra controls appended to the footer, after Apply / Reset

    Usage:
        <x-filter-card :action="route('admin.products.index')">
            <x-slot:hidden>
                <input type="hidden" name="search" value="{{ request('search') }}">
            </x-slot:hidden>

            <select name="status" class="form-input">...</select>
        </x-filter-card>
--}}
<form method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}" action="{{ $action }}"
    {{ $attributes->merge(['class' => 'premium-card filter-card']) }}>
    @if (strtoupper($method) !== 'GET')
        @csrf
        @method($method)
    @endif

    @isset($hidden)
        {{ $hidden }}
    @endisset

    <div class="{{ $grid }}">
        {{ $slot }}
    </div>

    <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
        <a href="{{ $reset ?? $action }}" class="ghost-button">
            <i class="fa-solid fa-rotate-left"></i> {{ $resetLabel }}
        </a>
        
        <button type="submit" class="filter-button">
            <i class="fa-solid fa-sliders"></i> {{ $applyLabel }}
        </button>

        @isset($actions)
            {{ $actions }}
        @endisset
    </div>
</form>
