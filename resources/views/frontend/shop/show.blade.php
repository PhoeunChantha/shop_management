@extends('frontend.layouts.frontend')
@section('title', $product['name'].' — T-Shirt Shop')

@push('head')
<style>
    .ut-pdp { display:grid; grid-template-columns:1.05fr .95fr; gap:48px; align-items:start; }
    .ut-pdp-info { position:sticky; top:96px; }
    @media (max-width:1024px){ .ut-pdp{ grid-template-columns:1fr; } .ut-pdp-info{ position:static; } }
    .ut-tab-btn{ border:0;background:none;font-family:var(--font-head);font-weight:600;font-size:14.5px;color:var(--text-2);padding:0 0 8px;border-bottom:2px solid transparent; }
    .ut-tab-btn.active{ color:var(--ink);border-bottom-color:var(--ink); }
</style>
@endpush

@section('content')
@php $off = $product['was'] ? round((1 - $product['price'] / $product['was']) * 100) : 0; @endphp
<div class="anim-up" data-product-scope style="padding-bottom:90px">
    <div class="ut-wrap" style="padding-top:28px">
        <div class="ut-pdp">
            {{-- GALLERY --}}
            <div>
                <div style="display:flex;flex-direction:column;gap:14px">
                    <div style="position:relative;border-radius:var(--r-xl);overflow:hidden">
                        <x-frontend.ph id="pdpMain" :tint="$product['tint']" :dark="$product['dark']" label="product · view 1" style="aspect-ratio:4/5" />
                        @if($product['was'])<span class="ut-tag ut-tag-sale" style="position:absolute;top:16px;left:16px">Save {{ $off }}%</span>@endif
                        <span class="icon-btn" style="position:absolute;bottom:16px;right:16px"><x-frontend.icon n="zoom" :size="18" /></span>
                    </div>
                    <div class="ut-row" style="gap:12px">
                        @for($i = 0; $i < $product['gallery']; $i++)
                            <button type="button" onclick="setThumb(this,{{ $i }})"
                                style="flex:1;border:0;padding:0;border-radius:var(--r-md);overflow:hidden;outline:{{ $i === 0 ? '2.5px solid var(--ink)' : '1px solid var(--border)' }};cursor:pointer">
                                <x-frontend.ph :tint="$product['tint']" :dark="$product['dark']" style="aspect-ratio:1" />
                            </button>
                        @endfor
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
                    <div style="font-family:var(--font-head);font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">Color — <span data-color-label>{{ $colors[$product['colors'][0]]['name'] }}</span></div>
                    <div class="ut-row" style="gap:10px">
                        @foreach($product['colors'] as $i => $c)
                            <span class="swatch {{ $i === 0 ? 'is-active' : '' }}" data-color="{{ $c }}" title="{{ $colors[$c]['name'] }}" style="background:{{ $colors[$c]['hex'] }};width:34px;height:34px"></span>
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
                        @foreach($product['sizes'] as $s)
                            <button type="button" class="ut-chip" data-size="{{ $s }}" style="width:58px;justify-content:center;padding:12px 0">{{ $s }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- add to cart --}}
                <div class="ut-row" style="gap:12px;margin-bottom:16px">
                    <div class="ut-row" style="border:1.5px solid var(--border);border-radius:var(--r-pill);overflow:hidden;background:#fff">
                        <button type="button" data-qty-step="-1" style="border:0;background:none;padding:9px 13px;color:var(--ink)"><x-frontend.icon n="minus" :size="16" /></button>
                        <span data-qty-value style="font-family:var(--font-head);font-weight:600;min-width:24px;text-align:center">1</span>
                        <button type="button" data-qty-step="1" style="border:0;background:none;padding:9px 13px;color:var(--ink)"><x-frontend.icon n="plus" :size="16" /></button>
                    </div>
                    <button type="button" class="ut-btn ut-btn-accent ut-btn-lg" style="flex:1"
                            data-add-to-cart data-require-size data-id="{{ $product['id'] }}" data-name="{{ $product['name'] }}" data-price="{{ $product['price'] }}" data-tint="{{ $product['tint'] }}">
                        <x-frontend.icon n="bag" :size="18" /> Add to bag
                    </button>
                </div>
                <a href="{{ route('frontend.checkout.index') }}" class="ut-btn ut-btn-ink ut-btn-block ut-btn-lg">Buy it now</a>

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
            @foreach(array_slice($reviews, 0, 2) as $r)
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
            @endforeach
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
                <x-frontend.ph :tint="$product['tint']" :dark="$product['dark']" style="width:48px;height:48px;border-radius:12px;flex-shrink:0" />
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
    function setThumb(btn, i){
        document.querySelectorAll('.ut-pdp button[onclick^="setThumb"]').forEach(b=>b.style.outline='1px solid var(--border)');
        btn.style.outline='2.5px solid var(--ink)';
        var lbl = document.querySelector('#pdpMain .ph-label'); if(lbl) lbl.textContent = 'product · view '+(i+1);
    }
    function setTab(btn, key){
        document.querySelectorAll('.ut-tab-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        ['details','fabric','shipping'].forEach(k=>{ document.getElementById('tab-'+k).style.display = k===key?'':'none'; });
    }
</script>
@endpush


