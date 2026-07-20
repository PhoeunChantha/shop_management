@props(['product'])
@php
    $p = $product;
    $off = $p['was'] ? round((1 - $p['price'] / $p['was']) * 100) : 0;
    $colors = $p['color_map'] ?? \App\Support\Catalog::colors();
    $slug = $p['slug'] ?? \Illuminate\Support\Str::slug($p['name'] ?? (string) $p['id']);
    $url = $p['url'] ?? route('frontend.shop.show', $slug);
@endphp
<article class="ut-card ut-pcard" style="position:relative">
    <a href="{{ $url }}" style="display:block">
        <div class="ut-pcard-media" style="position:relative">
            @if (!empty($p['image_url']))
                <img src="{{ $p['image_url'] }}" alt="{{ $p['name'] }}"
                    class="ut-pcard-image"
                    style="width:100%;aspect-ratio:3/4;object-fit:cover;display:block">
            @else
                <x-frontend.ph :tint="$p['tint']" :dark="$p['dark']" :label="'product · '.strtolower($p['cat'])" style="aspect-ratio:3/4" />
            @endif
            <div style="position:absolute;top:14px;left:14px;display:flex;gap:8px">
                @if($p['tag'] === 'sale')<span class="ut-tag ut-tag-sale">Sale</span>@elseif($p['tag'] === 'new')<span class="ut-tag ut-tag-new">New</span>@endif
                @if($p['was'])<span class="ut-tag ut-tag-hot">-{{ $off }}%</span>@endif
            </div>
            {{-- quick add (reveals on hover) --}}
            <div class="ut-pcard-add">
                <button type="button" class="ut-btn ut-btn-ink ut-btn-block ut-btn-sm"
                        data-add-to-cart data-no-open data-id="{{ $p['id'] }}" data-name="{{ $p['name'] }}"
                        data-price="{{ $p['price'] }}" data-tint="{{ $p['tint'] }}" data-color="{{ $p['colors'][0] }}" data-size="M">
                    <x-frontend.icon n="bag" :size="16" /> Quick add
                </button>
            </div>
        </div>
    </a>
    {{-- wishlist heart --}}
    <button type="button" class="icon-btn" data-wish="{{ $p['id'] }}" aria-label="Wishlist"
            style="position:absolute;top:12px;right:12px;width:38px;height:38px">
        <x-frontend.icon n="heart" :size="18" />
    </button>

    <a href="{{ $url }}" style="display:block;padding:15px 16px 17px">
        <div class="ut-row" style="justify-content:space-between;gap:8px">
            <h4 style="font-size:15.5px;font-weight:600">{{ $p['name'] }}</h4>
            @if($p['badge'])<span class="ut-tag ut-tag-soft" style="font-size:10px">{{ $p['badge'] }}</span>@endif
        </div>
        <div class="ut-row" style="gap:6px;margin:7px 0 12px">
            <x-frontend.stars :value="$p['rating']" :size="13" />
            <span class="muted" style="font-size:12.5px">{{ $p['rating'] }} · {{ $p['reviews'] }}</span>
        </div>
        <div class="ut-row" style="justify-content:space-between">
            <div class="ut-row" style="gap:8px">
                <span style="font-family:var(--font-head);font-weight:700;font-size:17px;color:var(--text)">${{ number_format((float) $p['price'], 2) }}</span>
                @if($p['was'])<span class="strike" style="font-size:13.5px;color:var(--text-2)">${{ number_format((float) $p['was'], 2) }}</span>@endif
            </div>
            <div class="ut-row" style="gap:6px">
                @foreach(array_slice($p['colors'], 0, 3) as $c)
                    @php($color = $colors[$c] ?? ['hex' => '#1a1a1d', 'name' => $c])
                    <span class="swatch" style="width:16px;height:16px;background:{{ $color['hex'] }}" title="{{ $color['name'] }}"></span>
                @endforeach
            </div>
        </div>
    </a>
</article>

