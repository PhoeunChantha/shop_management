@props(['tint' => 'linear-gradient(150deg,#eef2f7,#e2e8f0)', 'label' => null, 'dark' => false, 'style' => ''])
<div {{ $attributes->merge(['class' => 'ph']) }} style="--ph-tint:{{ $tint }};{{ $style }}">
    {{ $slot }}
    @if($label)<span class="ph-label {{ $dark ? 'on-dark' : '' }}">{{ $label }}</span>@endif
</div>
