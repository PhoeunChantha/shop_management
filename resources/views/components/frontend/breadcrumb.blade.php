@props(['items' => []])
{{-- items: array of [label, url|null]. Last item rendered as current. --}}
<nav class="muted ut-row" style="font-size:13px;gap:6px;flex-wrap:wrap" aria-label="Breadcrumb">
    @foreach($items as $i => [$label, $url])
        @if($i > 0)<span style="color:var(--text-3)">/</span>@endif
        @if($url && $i < count($items) - 1)
            <a href="{{ $url }}" style="color:inherit">{{ $label }}</a>
        @else
            <span style="color:var(--ink)">{{ $label }}</span>
        @endif
    @endforeach
</nav>
