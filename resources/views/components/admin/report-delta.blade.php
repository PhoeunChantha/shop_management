@props(['data' => null])

@if (is_array($data) && $data['change'] !== null)
    @php($dir = $data['direction'])
    <span class="report-delta report-delta--{{ $dir }}">
        <i class="fa-solid fa-arrow-{{ $dir === 'up' ? 'up' : ($dir === 'down' ? 'down' : 'right') }}"></i>
        {{ $data['change'] > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($data['change'], 1), '0'), '.') }}%
        <span class="report-delta__label">{{ __('vs prev.') }}</span>
    </span>
@elseif (is_array($data))
    <span class="report-delta report-delta--flat">
        <i class="fa-solid fa-minus"></i>{{ __('no prior data') }}
    </span>
@endif
