@props([
    // [['label' => ..., 'value' => formatted string, 'raw' => number, 'decimals' => 0|2, 'icon' => 'fa-…', 'tone' => 'emerald|blue|amber|rose|violet'], ...]
    'cards' => [],
])

{{-- KPI stat bar: icon chip, value that counts up on load (data-count-up), label. --}}
<div {{ $attributes->merge(['class' => 'order-stats']) }}>
    @foreach ($cards as $card)
        <div class="order-stat">
            <span class="order-stat__icon order-stat__icon--{{ $card['tone'] }}"><i class="fa-solid {{ $card['icon'] }}"></i></span>
            <div>
                <div class="order-stat__value" data-count-up="{{ (float) ($card['raw'] ?? 0) }}" data-count-decimals="{{ $card['decimals'] ?? 0 }}">{{ $card['value'] }}</div>
                <div class="order-stat__label">{{ $card['label'] }}</div>
            </div>
        </div>
    @endforeach
</div>
