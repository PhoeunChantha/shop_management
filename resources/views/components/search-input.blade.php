@props([
    'action' => null,
    'name' => 'search',
    'value' => null,
    'placeholder' => 'Search...',
    'auto' => true,
    'debounce' => 450,
])

@php
    $current = $value ?? request($name);
    // Preserve every other active query parameter (filters, per page, etc.) but reset paging.
    $preserved = collect(request()->except([$name, 'page']));
@endphp

<form
    method="GET"
    action="{{ $action ?? url()->current() }}"
    class="toolbar-form"
    role="search"
    @if ($auto)
        x-data
        x-init="$nextTick(() => {
            const el = $refs.field;
            if (el.value) {
                el.focus();
                const len = el.value.length;
                el.setSelectionRange(len, len);
            }
        })"
    @endif
>
    @foreach ($preserved as $key => $val)
        @if (is_array($val))
            @foreach ($val as $item)
                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
        @endif
    @endforeach

    <label class="search-control">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input
            type="search"
            name="{{ $name }}"
            value="{{ $current }}"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            @if ($auto)
                x-ref="field"
                @input.debounce.{{ (int) $debounce }}ms="$el.form.submit()"
                @search="$el.form.submit()"
            @endif
        >
    </label>
</form>
