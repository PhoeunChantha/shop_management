@props(['value' => 5, 'size' => 14])
@php $v = round($value); @endphp
<span class="stars" aria-label="{{ $value }} out of 5">
    @for($i = 1; $i <= 5; $i++)
        <x-frontend.icon n="star" :size="$size" :cls="$i <= $v ? '' : 'empty'" />
    @endfor
</span>
