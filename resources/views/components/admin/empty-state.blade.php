@props([
    'icon' => 'fa-solid fa-inbox',
    'title' => 'No records found',
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'empty-state admin-empty-state']) }}>
    <i class="{{ $icon }}"></i>
    <strong>{{ $title }}</strong>
    @if ($message)
        <span>{{ $message }}</span>
    @endif
</div>
