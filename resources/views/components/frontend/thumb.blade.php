@props(['src' => null, 'alt' => '', 'tint' => '', 'dark' => false, 'style' => ''])
{{-- Product thumbnail: the real image when there is one, the tinted placeholder otherwise. --}}
@if($src)
    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" decoding="async" {{ $attributes }} style="object-fit:cover;display:block;flex-shrink:0;{{ $style }}">
@else
    <x-frontend.ph :tint="$tint" :dark="$dark" :style="$style" {{ $attributes }} />
@endif
