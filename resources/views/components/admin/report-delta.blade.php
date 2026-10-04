@props([
    // Comparison::of() result: ['change' => ?float, 'direction' => up|down|flat, ...]
    'data' => null,
    // A rise is bad (refunds, cancellations): flip the good/bad colouring.
    'inverse' => false,
])

@if (is_array($data))
    @php
        $dir = $data['direction'];
        $good = $inverse ? $dir === 'down' : $dir === 'up';
        $tone = $data['change'] === null || $dir === 'flat' ? 'is-flat' : ($good ? 'is-up' : 'is-down');
        $icon = ['up' => 'fa-arrow-trend-up', 'down' => 'fa-arrow-trend-down'][$dir] ?? 'fa-minus';
    @endphp
    <span class="kpi-delta {{ $tone }}"
        title="{{ __('Previous period') }}: {{ is_float($data['previous']) && floor($data['previous']) != $data['previous'] ? number_format($data['previous'], 2) : number_format($data['previous']) }}">
        <i class="fa-solid {{ $data['change'] === null ? 'fa-minus' : $icon }}"></i>
        @if ($data['change'] === null)
            {{ __('No prior data') }}
        @else
            {{ $data['change'] > 0 ? '+' : '' }}{{ number_format($data['change'], 1) }}%
        @endif
    </span>
@endif
