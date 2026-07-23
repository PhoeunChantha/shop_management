@extends('frontend.layouts.frontend')
@section('title', $product['name'].' — T-Shirt Shop')

@push('head')
<style>
    .ut-pdp { display:grid; grid-template-columns:minmax(340px, 520px) minmax(0, 1fr); gap:54px; align-items:start; justify-content:center; }
    .ut-pdp-gallery { width:100%; max-width:520px; justify-self:end; }
    .ut-pdp-main-media { max-height:620px; }
    .ut-pdp-main-media img,
    .ut-pdp-main-media .ph { max-height:620px; object-fit:cover; }
    .ut-pdp-thumbs { display:grid; grid-auto-flow:column; grid-auto-columns:76px; gap:10px; justify-content:start; overflow-x:auto; padding:2px 2px 8px; scrollbar-width:thin; }
    .ut-pdp-thumb { width:76px; border:0; padding:0; border-radius:14px; overflow:hidden; cursor:pointer; background:#fff; flex:none; }
    .ut-pdp-thumb img,
    .ut-pdp-thumb .ph { width:100%; aspect-ratio:1; object-fit:cover; display:block; }
    .ut-pdp-info { position:sticky; top:96px; }
    .ut-purchase-box{ display:grid; grid-template-columns:132px minmax(0,1fr); gap:10px; align-items:stretch; margin-bottom:10px; }
    .ut-qty-card{ border:1px solid var(--border); border-radius:15px; background:rgba(255,255,255,.76); overflow:hidden; display:grid; grid-template-columns:38px 1fr 38px; min-height:52px; box-shadow:0 8px 18px rgba(31,25,17,.035); }
    .ut-qty-card button{ border:0; background:transparent; color:var(--ink); display:grid; place-items:center; }
    .ut-qty-card [data-qty-value]{ align-self:center; justify-self:center; font-family:var(--font-head); font-weight:700; font-size:16px; min-width:22px; text-align:center; }
    .ut-purchase-add{ min-height:52px; border-radius:13px !important; font-size:13.5px; letter-spacing:.08em; }
    .ut-purchase-buy{ min-height:52px; border-radius:13px !important; font-size:13.5px; letter-spacing:.08em; }
    .ut-purchase-total{ display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; padding:9px 1px 0; border-top:1px solid rgba(122,100,70,.16); }
    .ut-purchase-total span{ font-size:13.5px; color:var(--text-2); }
    .ut-purchase-total strong{ font-family:var(--font-head); font-size:20px; color:var(--ink); }
    @media (max-width:1024px){ .ut-pdp{ grid-template-columns:1fr; gap:32px; } .ut-pdp-gallery{ max-width:640px; justify-self:center; } .ut-pdp-info{ position:static; } }
    @media (max-width:640px){ .ut-pdp-gallery{ max-width:none; } .ut-pdp-main-media, .ut-pdp-main-media img, .ut-pdp-main-media .ph{ max-height:none; } .ut-pdp-thumbs{ grid-auto-columns:68px; } .ut-pdp-thumb{ width:68px; } .ut-purchase-box{ grid-template-columns:1fr; } }
    .ut-tab-btn{ border:0;background:none;font-family:var(--font-head);font-weight:600;font-size:14.5px;color:var(--text-2);padding:0 0 8px;border-bottom:2px solid transparent; }
    .ut-tab-btn.active{ color:var(--ink);border-bottom-color:var(--ink); }
</style>
@endpush

@section('content')
@php
    $off = $product['was'] ? round((1 - $product['price'] / $product['was']) * 100) : 0;
    $firstColor = $product['colors'][0] ?? 'black';
    $firstColorMeta = $colors[$firstColor] ?? ['name' => ucfirst($firstColor), 'hex' => '#1a1a1d'];
    $productImages = $product['images'] ?? [];
    $mainImage = $productImages[0] ?? $product['image_url'] ?? null;
@endphp
<div class="anim-up" data-product-scope style="padding-bottom:90px">
    <div class="ut-wrap" style="padding-top:28px">
        <div class="ut-pdp">
            {{-- GALLERY --}}
            <div class="ut-pdp-gallery">
                <div style="display:flex;flex-direction:column;gap:14px">
                    <div class="ut-pdp-main-media" style="position:relative;border-radius:var(--r-xl);overflow:hidden">
                        @if($mainImage)
                            <img id="pdpMainImg" src="{{ $mainImage }}" alt="{{ $product['name'] }}" style="width:100%;aspect-ratio:4/5;object-fit:cover;display:block">
                        @else
                            <x-frontend.ph id="pdpMain" :tint="$product['tint']" :dark="$product['dark']" label="product Â· view 1" style="aspect-ratio:4/5" />
                        @endif
                        @if($product['was'])<span class="ut-tag ut-tag-sale" style="position:absolute;top:16px;left:16px">Save {{ $off }}%</span>@endif
                        <span class="icon-btn" style="position:absolute;bottom:16px;right:16px"><x-frontend.icon n="zoom" :size="18" /></span>
                    </div>
                    <div class="ut-pdp-thumbs">
                        @forelse($productImages as $i => $image)
                            <button type="button" class="ut-pdp-thumb" onclick="setThumb(this,{{ $i }}, @js($image))"
                                style="outline:{{ $i === 0 ? '2.5px solid var(--ink)' : '1px solid var(--border)' }}">
                                <img src="{{ $image }}" alt="{{ $product['name'] }} view {{ $i + 1 }}">
                            </button>
                        @empty
                            @for($i = 0; $i < $product['gallery']; $i++)
                                <button type="button" class="ut-pdp-thumb" onclick="setThumb(this,{{ $i }})"
                                    style="outline:{{ $i === 0 ? '2.5px solid var(--ink)' : '1px solid var(--border)' }}">
                                    <x-frontend.ph :tint="$product['tint']" :dark="$product['dark']" style="aspect-ratio:1" />
                                </button>
                            @endfor
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- INFO --}}
            <div class="ut-pdp-info">
                <x-frontend.breadcrumb :items="[['Shop', route('frontend.shop.index')], [$product['cat'], route('frontend.shop.index')], [$product['name'], null]]" />
                <div class="ut-row" style="justify-content:space-between;align-items:flex-start;gap:12px;margin-top:10px">
                    <h1 style="font-size:clamp(28px,3.4vw,40px);line-height:1.05">{{ $product['name'] }}</h1>
                    <div class="ut-row" style="gap:8px">
                        <button type="button" class="icon-btn" data-wish="{{ $product['id'] }}" aria-label="Wishlist"><x-frontend.icon n="heart" :size="18" /></button>
                        <button type="button" class="icon-btn" style="box-shadow:none;background:var(--bg)" onclick="utToast('Share link copied')"><x-frontend.icon n="share" :size="18" /></button>
                    </div>
                </div>
                <div class="ut-row" style="gap:10px;margin:12px 0 16px">
                    <x-frontend.stars :value="$product['rating']" /><span class="muted" style="font-size:14px">{{ $product['rating'] }} · {{ $product['reviews'] }} reviews</span>
                    <span class="ut-tag ut-tag-success"><span style="width:6px;height:6px;border-radius:6px;background:var(--success);display:inline-block"></span> In stock</span>
                </div>
                <div class="ut-row" style="gap:12px;margin-bottom:24px">
                    <span style="font-family:var(--font-head);font-weight:700;font-size:32px">${{ $product['price'] }}</span>
                    @if($product['was'])<span class="strike" style="font-size:20px">${{ $product['was'] }}</span><span class="ut-tag ut-tag-sale">-{{ $off }}%</span>@endif
                </div>

                {{-- color --}}
                <div style="margin-bottom:22px" data-color-group>
                    <div style="font-family:var(--font-head);font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">Color — <span data-color-label>{{ $firstColorMeta['name'] }}</span></div>
                    <div class="ut-row" style="gap:10px">
                        @foreach($product['colors'] as $i => $c)
                            @php($color = $colors[$c] ?? ['name' => ucfirst($c), 'hex' => '#1a1a1d'])
                            <span class="swatch {{ $i === 0 ? 'is-active' : '' }}" data-color="{{ $c }}" title="{{ $color['name'] }}" style="background:{{ $color['hex'] }};width:34px;height:34px"></span>
                        @endforeach
                    </div>
                </div>

                {{-- size --}}
                <div style="margin-bottom:24px" data-size-group>
                    <div class="ut-row" style="justify-content:space-between;margin-bottom:12px">
                        <div style="font-family:var(--font-head);font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.06em">Select size</div>
                        <button class="ut-link" style="font-size:13px"><x-frontend.icon n="ruler" :size="15" /> Size guide</button>
                    </div>
                    <div style="display:flex;gap:9px;flex-wrap:wrap">
                        @foreach($product['sizes'] as $i => $s)
                            <button type="button" class="ut-chip {{ $i === 0 ? 'is-active' : '' }}" data-size="{{ $s }}" style="width:58px;justify-content:center;padding:12px 0">{{ $s }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- add to cart --}}
                <div class="ut-purchase-box">
                    <div class="ut-qty-card" aria-label="Quantity">
                        <button type="button" data-qty-step="-1" aria-label="Decrease quantity"><x-frontend.icon n="minus" :size="16" /></button>
                        <span data-qty-value>1</span>
                        <button type="button" data-qty-step="1" aria-label="Increase quantity"><x-frontend.icon n="plus" :size="16" /></button>
                    </div>
                    <button type="button" class="ut-btn ut-btn-accent ut-purchase-add"
                            data-add-to-cart data-require-size data-id="{{ $product['id'] }}" data-name="{{ $product['name'] }}" data-price="{{ $product['price'] }}" data-tint="{{ $product['tint'] }}">
                        <x-frontend.icon n="bag" :size="18" /> Add to bag
                    </button>
                </div>
                <div class="ut-purchase-total">
                    <span>Total for <span data-pdp-qty-label>1</span> <span data-pdp-item-label>item</span></span>
                    <strong data-pdp-total data-unit-price="{{ (float) $product['price'] }}">${{ number_format((float) $product['price'], 2) }}</strong>
                </div>
                <a href="{{ route('frontend.checkout.index') }}" class="ut-btn ut-btn-ink ut-btn-block ut-purchase-buy">Buy it now</a>

                <div class="ut-row" style="gap:18px;margin-top:22px;flex-wrap:wrap">
                    @foreach([['truck', 'Free shipping over $75'], ['refresh', '30-day returns'], ['shield', 'Secure checkout']] as [$ic, $t])
                        <div class="ut-row muted" style="gap:8px;font-size:13px"><span style="color:var(--blue)"><x-frontend.icon :n="$ic" :size="17" /></span>{{ $t }}</div>
                    @endforeach
                </div>

                {{-- tabs --}}
                <div style="margin-top:30px;border-top:1px solid var(--border);padding-top:18px">
                    <div class="ut-row" style="gap:24px;margin-bottom:16px">
                        <button type="button" class="ut-tab-btn active" onclick="setTab(this,'details')">Details</button>
                        <button type="button" class="ut-tab-btn" onclick="setTab(this,'fabric')">Fabric &amp; care</button>
                        <button type="button" class="ut-tab-btn" onclick="setTab(this,'shipping')">Shipping</button>
                    </div>
                    <p class="muted" id="tab-details" style="font-size:14.5px;line-height:1.7">{{ $product['desc'] }}</p>
                    <p class="muted" id="tab-fabric" style="display:none;font-size:14.5px;line-height:1.7">100% organic combed cotton, 240gsm. Machine wash cold, tumble dry low. Garment-dyed — minor variation in tone is part of the character.</p>
                    <p class="muted" id="tab-shipping" style="display:none;font-size:14.5px;line-height:1.7">Free standard shipping on orders over $75 (2–4 business days). Express available at checkout. Free 30-day returns on unworn items.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- reviews --}}
    <section class="ut-wrap" style="margin-top:72px">
        <div class="ut-sec-head"><div><span class="ut-eyebrow">Verified reviews</span><h2 style="margin-top:8px">{{ $product['rating'] }} · {{ $product['reviews'] }} reviews</h2></div></div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:18px" class="ut-rev-grid">
            @forelse(array_slice($reviews, 0, 2) as $r)
                <div class="ut-card" style="padding:24px">
                    <div class="ut-row" style="justify-content:space-between;margin-bottom:12px">
                        <div class="ut-row" style="gap:11px">
                            <span style="width:42px;height:42px;border-radius:50%;background:var(--bg);display:grid;place-items:center;font-family:var(--font-head);font-weight:700;color:var(--text-2)">{{ $r['name'][0] }}</span>
                            <div><div style="font-family:var(--font-head);font-weight:600">{{ $r['name'] }}</div><div class="muted" style="font-size:12.5px">{{ $r['city'] }}</div></div>
                        </div>
                        <x-frontend.stars :value="$r['rating']" />
                    </div>
                    <p style="font-size:14.5px;line-height:1.6;margin:0">"{{ $r['text'] }}"</p>
                </div>
            @empty
                <div class="ut-card" style="padding:24px;grid-column:1/-1;text-align:center">
                    <p class="muted" style="margin:0;font-size:14.5px">No reviews yet — be the first to share your thoughts.</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- related --}}
    <section class="ut-wrap" style="margin-top:72px">
        <div class="ut-sec-head"><div><span class="ut-eyebrow">Complete the look</span><h2 style="margin-top:8px">You may also like</h2></div></div>
        <div class="ut-rail">
            @foreach($related as $p)<x-frontend.product-card :product="$p" />@endforeach
        </div>
    </section>

    {{-- sticky add-to-cart bar --}}
    <div class="ut-stickybar">
        <div class="ut-wrap ut-row" style="justify-content:space-between;padding:14px 24px;gap:16px">
            <div class="ut-row" style="gap:14px">
                @if($mainImage)
                    <img src="{{ $mainImage }}" alt="{{ $product['name'] }}" style="width:48px;height:48px;border-radius:12px;flex-shrink:0;object-fit:cover">
                @else
                    <x-frontend.ph :tint="$product['tint']" :dark="$product['dark']" style="width:48px;height:48px;border-radius:12px;flex-shrink:0" />
                @endif
                <div class="ut-hide-mobile">
                    <div style="font-family:var(--font-head);font-weight:700;font-size:15px">{{ $product['name'] }}</div>
                    <div class="muted" style="font-size:13px">{{ $product['cat'] }}</div>
                </div>
            </div>
            <div class="ut-row" style="gap:14px">
                <span class="ut-hide-mobile" style="font-family:var(--font-head);font-weight:700;font-size:20px">${{ $product['price'] }}</span>
                <button type="button" class="ut-btn ut-btn-accent ut-btn-lg" data-add-to-cart data-require-size
                        data-id="{{ $product['id'] }}" data-name="{{ $product['name'] }}" data-price="{{ $product['price'] }}" data-tint="{{ $product['tint'] }}">
                    <x-frontend.icon n="bag" :size="17" /> Add to bag
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function setThumb(btn, i, image){
        document.querySelectorAll('.ut-pdp button[onclick^="setThumb"]').forEach(b=>b.style.outline='1px solid var(--border)');
        btn.style.outline='2.5px solid var(--ink)';
        var img = document.getElementById('pdpMainImg'); if(img && image) img.src = image;
        var lbl = document.querySelector('#pdpMain .ph-label'); if(lbl) lbl.textContent = 'product · view '+(i+1);
    }
    function setTab(btn, key){
        document.querySelectorAll('.ut-tab-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        ['details','fabric','shipping'].forEach(k=>{ document.getElementById('tab-'+k).style.display = k===key?'':'none'; });
    }
    function updatePdpTotal(){
        var scope = document.querySelector('[data-product-scope]');
        if(!scope) return;
        var qty = Math.max(1, Number((scope.querySelector('[data-qty-value]') || {}).textContent || 1));
        var total = scope.querySelector('[data-pdp-total]');
        var label = scope.querySelector('[data-pdp-qty-label]');
        var itemLabel = scope.querySelector('[data-pdp-item-label]');
        if(label) label.textContent = qty;
        if(itemLabel) itemLabel.textContent = qty === 1 ? 'item' : 'items';
        if(total){
            var unit = Number(total.getAttribute('data-unit-price') || 0);
            total.textContent = '$' + (unit * qty).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }
    document.addEventListener('click', function(e){
        if(e.target.closest('[data-product-scope] [data-qty-step]')) updatePdpTotal();
    });
    updatePdpTotal();
</script>
@endpush


