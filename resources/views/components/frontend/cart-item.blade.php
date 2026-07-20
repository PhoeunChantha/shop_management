@props(['item', 'big' => false])
{{-- Server-rendered cart line (e.g. order items). Live cart uses JS-rendered lines. --}}
@php $colors = app(\App\Services\FrontendProductService::class)->colors(); $c = $colors[$item['color']] ?? ['name' => $item['color'], 'hex' => '#ccc']; @endphp
<div class="ut-row" style="gap:14px;padding:{{ $big ? '18px' : '14px' }} 0;border-bottom:1px solid var(--border-2);align-items:flex-start">
    <x-frontend.ph :tint="$item['tint'] ?? 'linear-gradient(150deg,#eef2f7,#e2e8f0)'"
        style="width:{{ $big ? '96px' : '72px' }};height:{{ $big ? '120px' : '90px' }};border-radius:14px;flex-shrink:0" />
    <div style="flex:1;min-width:0">
        <div class="ut-row" style="justify-content:space-between;gap:8px">
            <div style="font-family:var(--font-head);font-weight:600;font-size:{{ $big ? '16px' : '14.5px' }}">{{ $item['name'] }}</div>
            <span style="font-family:var(--font-head);font-weight:700">${{ $item['price'] * $item['qty'] }}</span>
        </div>
        <div class="ut-row muted" style="gap:8px;font-size:13px;margin-top:5px">
            <span class="ut-row" style="gap:5px"><span class="swatch" style="width:14px;height:14px;background:{{ $c['hex'] }}"></span> {{ $c['name'] }}</span>
            <span>·</span><span>Size {{ $item['size'] }}</span>
            <span>·</span><span>Qty {{ $item['qty'] }}</span>
        </div>
    </div>
</div>
