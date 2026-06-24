{{-- T-SHIRT SHOP — inline SVG icon helper (Lucide-style).
     Usage: @include('frontend.partials.icon', ['n' => 'bag', 'size' => 20, 'sw' => 1.7])
     or via the <x-frontend.icon> component. --}}
@php
    $size = $size ?? 20;
    $sw   = $sw ?? 1.7;
    $cls  = $cls ?? '';
    $style = $style ?? '';
    $paths = [
        'search'  => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'bag'     => '<path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8a3 3 0 0 1 6 0"/>',
        'heart'   => '<path d="M12 20s-7-4.5-9.5-9A4.5 4.5 0 0 1 12 6a4.5 4.5 0 0 1 9.5 5c-2.5 4.5-9.5 9-9.5 9Z"/>',
        'user'    => '<circle cx="12" cy="8" r="4"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'menu'    => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'close'   => '<path d="M6 6l12 12M18 6 6 18"/>',
        'arrowR'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrowL'  => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'chevD'   => '<path d="m6 9 6 6 6-6"/>',
        'chevR'   => '<path d="m9 6 6 6-6 6"/>',
        'plus'    => '<path d="M12 5v14M5 12h14"/>',
        'minus'   => '<path d="M5 12h14"/>',
        'check'   => '<path d="M4 12l5 5L20 6"/>',
        'checkC'  => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5L16 9"/>',
        'trash'   => '<path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/>',
        'filter'  => '<path d="M3 5h18l-7 8v6l-4-2v-4L3 5Z"/>',
        'grid'    => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'zoom'    => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M11 8v6M8 11h6"/>',
        'share'   => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5 15.4 17M15.4 7 8.6 10.5"/>',
        'truck'   => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/>',
        'shield'  => '<path d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'refresh' => '<path d="M3 12a9 9 0 0 1 15-6.7L21 8M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M21 4v4h-4M3 20v-4h4"/>',
        'tag'     => '<path d="M3 12V4h8l9 9-8 8-9-9Z"/><circle cx="8" cy="8" r="1.4" fill="currentColor"/>',
        'spark'   => '<path d="M12 3v6M12 15v6M3 12h6M15 12h6"/><path d="m6 6 3 3M15 15l3 3M18 6l-3 3M9 15l-3 3"/>',
        'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'mail'    => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m4 7 8 6 8-6"/>',
        'pin'     => '<path d="M12 21s7-6 7-11a7 7 0 1 0-14 0c0 5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'home'    => '<path d="M4 11l8-7 8 7M6 10v9h12v-9"/>',
        'card'    => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3 10h18"/>',
        'lock'    => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'ig'      => '<rect x="4" y="4" width="16" height="16" rx="5"/><circle cx="12" cy="12" r="3.5"/><circle cx="17" cy="7" r="1" fill="currentColor"/>',
        'ruler'   => '<rect x="3" y="8" width="18" height="8" rx="1.5"/><path d="M7 8v3M11 8v4M15 8v3M19 8v4"/>',
        'flame'   => '<path d="M12 3c1 3 4 4 4 8a4 4 0 0 1-8 0c0-1.5.5-2.5 1.5-3.5C10 9 11 7 12 3Z"/>',
        'bell'    => '<path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6Z"/><path d="M10 19a2 2 0 0 0 4 0"/>',
        'box'     => '<path d="M3 7l9-4 9 4-9 4-9-4Z"/><path d="M3 7v10l9 4 9-4V7M12 11v10"/>',
        'info'    => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'star'    => '<path d="M12 2.5l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.2 20.9l1.1-6.5L2.6 9.8l6.5-.9L12 2.5Z"/>',
    ];
    $isFill = in_array($n, ['star']);
@endphp
<svg class="{{ $cls }}" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24"
     fill="{{ $isFill ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="{{ $isFill ? 0 : $sw }}"
     stroke-linecap="round" stroke-linejoin="round" style="{{ $style }};flex-shrink:0" aria-hidden="true">{!! $paths[$n] ?? '' !!}</svg>

