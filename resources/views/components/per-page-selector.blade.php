@props([
    'action' => null,
    'name' => 'per_page',
    'current' => null,
    'options' => [5, 10, 25, 50],
    'label' => 'Show',
])

@php
    $selected = $current ?? request($name);
    // Preserve every other active query parameter (filters, etc.) but reset paging.
    $preserved = collect(request()->except([$name, 'page']));
@endphp

<form method="GET" action="{{ $action ?? url()->current() }}" class="toolbar-form">
    @foreach ($preserved as $key => $value)
        @if (is_array($value))
            @foreach ($value as $item)
                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <label class="per-page-control">
        <span>{{ $label }}</span>
        <select name="{{ $name }}" onchange="this.form.requestSubmit()">
            @foreach ($options as $size)
                <option value="{{ $size }}" @selected((int) $selected === (int) $size)>{{ $size }}</option>
            @endforeach
        </select>
    </label>
</form>
