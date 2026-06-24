@extends('frontend.layouts.frontend')
@section('title', 'T-Shirt Shop — Premium Streetwear Tees')

@push('head')
<style>
    .ut-coll-grid { display:grid; grid-template-columns:2fr 1fr 1fr; grid-template-rows:repeat(2,1fr); gap:18px; height:460px; }
    @media (max-width:1024px){ .ut-coll-grid{ grid-template-columns:repeat(2,1fr); grid-template-rows:auto; height:auto; } .ut-coll-grid > *{ grid-column:auto !important; grid-row:auto !important; aspect-ratio:4/3; } }
    @media (max-width:767px){ .ut-coll-grid{ grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="anim-up">

    {{-- HERO --}}
    <section class="ut-hero" aria-label="Featured collections" style="display:block;height:590px;min-height:590px">
        <div id="utHeroCarousel" class="ut-hero-carousel" style="display:block;height:590px;min-height:590px">
            <div class="ut-hero-dots"><button type="button" data-hero-slide="0" class="active" aria-current="true" aria-label="New arrivals"></button><button type="button" data-hero-slide="1" aria-label="Best sellers"></button><button type="button" data-hero-slide="2" aria-label="Graphic collection"></button><button type="button" data-hero-slide="3" aria-label="Flash sale"></button></div>
            <div class="ut-hero-track">
                @foreach([
                    ['kicker' => '01 / New arrivals', 'title' => 'Premium oversized<br>t-shirts.', 'copy' => 'Built for comfort. Designed for style. Garment-dyed heavyweight cotton with the relaxed structure you reach for every day.', 'primary' => 'Shop new arrivals', 'secondary' => 'Explore the edit', 'class' => 'ut-hero-new', 'trust' => '240gsm organic cotton'],
                    ['kicker' => '02 / Best sellers', 'title' => 'The tees<br>everyone loves.', 'copy' => 'The most-reordered fits in our collection. Proven weight, precise proportions, and color that only gets better with time.', 'primary' => 'Shop best sellers', 'secondary' => 'See the reviews', 'class' => 'ut-hero-best', 'trust' => '12k+ five-star reviews'],
                    ['kicker' => '03 / Graphic collection', 'title' => 'Bold designs.<br>Everyday wear.', 'copy' => 'Limited-run artwork meets our signature heavyweight base. Made to be noticed, built to stay in rotation.', 'primary' => 'Shop graphic tees', 'secondary' => 'View lookbook', 'class' => 'ut-hero-graphic', 'trust' => 'Limited edition print runs'],
                    ['kicker' => '04 / Flash sale', 'title' => 'Up to 40% off.<br>Last call.', 'copy' => 'Final sizes from past drops. Once a color or size is gone, it is gone. Move fast on the pieces you missed.', 'primary' => 'Shop the sale', 'secondary' => 'View all offers', 'class' => 'ut-hero-sale', 'trust' => 'Ends Sunday at midnight'],
                ] as $index => $slide)
                    <div class="ut-hero-item {{ $index === 0 ? 'active' : '' }}"><div class="ut-hero-slide {{ $slide['class'] }}"><div class="ut-wrap ut-hero-layout"><div class="ut-hero-copy"><span class="ut-hero-kicker">{{ $slide['kicker'] }}</span><h1>{!! $slide['title'] !!}</h1><p>{{ $slide['copy'] }}</p><div class="ut-hero-ctas"><a href="{{ route('frontend.shop.index') }}" class="ut-btn ut-btn-accent ut-btn-lg">{{ $slide['primary'] }} <x-frontend.icon n="arrowR" :size="18" /></a><a href="{{ route('frontend.shop.index') }}" class="ut-btn ut-hero-secondary">{{ $slide['secondary'] }}</a></div></div><div class="ut-hero-product" aria-hidden="true"><div class="ut-hero-product-card"><span>{{ $slide['trust'] }}</span><b>URBAN<br>THREAD</b></div></div></div><div class="ut-wrap"><div class="ut-hero-trust"><span><b>4.9</b> rating</span><i></i><span><b>12k+</b> reviews</span><i></i><span><b>240gsm</b> organic cotton</span><i></i><span><b>30-day</b> returns</span><i></i><span><b>Free</b> shipping $75+</span></div></div></div></div>
                @endforeach
            </div>
            <button class="ut-hero-arrow ut-hero-arrow-prev" type="button" data-hero-action="prev" aria-label="Previous slide"><x-frontend.icon n="arrowL" :size="22" /></button><button class="ut-hero-arrow ut-hero-arrow-next" type="button" data-hero-action="next" aria-label="Next slide"><x-frontend.icon n="arrowR" :size="22" /></button>
        </div>
    </section>

    {{-- COLLECTIONS --}}
    <section class="ut-wrap" style="margin-top:72px">
        <div class="ut-sec-head">
            <div><span class="ut-eyebrow">Curated</span><h2 style="margin-top:8px">Featured collections</h2></div>
            <a href="{{ route('frontend.shop.index') }}" class="ut-link">All collections <x-frontend.icon n="arrowR" :size="16" /></a>
        </div>
        <div class="ut-coll-grid">
            @foreach($collections as $i => $c)
                <a href="{{ route('frontend.shop.index') }}" class="ut-coll-cell" style="position:relative;border-radius:var(--r-lg);overflow:hidden;{{ $i === 0 ? 'grid-row:span 2;' : '' }}">
                    <x-frontend.ph :tint="$c['tint']" :dark="$c['dark']" style="position:absolute;inset:0" />
                    <div style="position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(0,0,0,.5))"></div>
                    <div style="position:absolute;left:0;right:0;bottom:0;padding:{{ $i === 0 ? '28px' : '18px' }};color:#fff">
                        <div style="font-family:var(--font-head);font-weight:700;font-size:{{ $i === 0 ? '28px' : '18px' }}">{{ $c['name'] }}</div>
                        <div style="opacity:.8;font-size:13px;margin-top:2px">{{ $c['sub'] }} · {{ $c['count'] }} styles</div>
                    </div>
                    <span class="icon-btn" style="position:absolute;top:14px;right:14px;background:#fff"><x-frontend.icon n="arrowR" :size="18" /></span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- BEST SELLERS --}}
    @include('frontend.partials.product-section', ['eyebrow' => 'Most wanted', 'title' => 'Best sellers', 'items' => $best])

    {{-- FLASH SALE --}}
    <section class="ut-wrap" style="margin-top:72px">
        <div style="background:var(--ink);border-radius:var(--r-xl);padding:clamp(24px,4vw,44px);color:#fff;position:relative;overflow:hidden" data-countdown="{{ 7*3600 + 42*60 + 18 }}">
            <div style="position:absolute;top:-60px;right:-40px;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,rgba(249,115,22,.45),transparent 70%)"></div>
            <div class="ut-row" style="justify-content:space-between;flex-wrap:wrap;gap:18px;margin-bottom:28px;position:relative">
                <div><span class="ut-eyebrow" style="color:var(--accent)"><x-frontend.icon n="flame" :size="13" style="vertical-align:-2px" /> Flash sale</span><h2 style="color:#fff;font-size:clamp(26px,3vw,38px);margin-top:8px">Up to 40% off — ends soon</h2></div>
                <div class="ut-row" style="gap:10px">@foreach([['data-h', 'hrs'], ['data-m', 'min'], ['data-s', 'sec']] as $i => [$attr, $lab])<div style="text-align:center;background:rgba(255,255,255,.1);border-radius:14px;padding:12px 14px;min-width:64px"><div class="mono" style="font-size:28px;font-weight:700;font-variant-numeric:tabular-nums" {{ $attr }}>00</div><div style="font-size:11px;opacity:.65;text-transform:uppercase;letter-spacing:.1em">{{ $lab }}</div></div>@if($i < 2)<span style="font-size:26px;opacity:.5">:</span>@endif @endforeach</div>
            </div>
            <div class="ut-rail" style="position:relative">@foreach($flash as $p)<div style="background:#fff;border-radius:var(--r-lg);overflow:hidden"><x-frontend.product-card :product="$p" /></div>@endforeach</div>
        </div>
    </section>

    {{-- NEW ARRIVALS --}}
    @include('frontend.partials.product-section', ['eyebrow' => 'Just dropped', 'title' => 'New arrivals', 'items' => $fresh])

    {{-- TRUST BAR --}}
    <section class="ut-wrap" style="margin-top:72px"><div class="ut-trust" style="background:#fff;border:1px solid var(--border-2);border-radius:var(--r-lg);padding:26px;box-shadow:var(--sh-1)">@foreach([['truck', 'Free shipping', 'On orders over $75'], ['refresh', '30-day returns', 'No-questions-asked'], ['shield', 'Secure checkout', '256-bit encryption'], ['spark', 'Carbon neutral', 'Every delivery']] as [$ic, $a, $b])<div class="ut-row" style="gap:14px"><span style="width:48px;height:48px;border-radius:14px;background:var(--bg);display:grid;place-items:center;color:var(--blue);flex-shrink:0"><x-frontend.icon :n="$ic" :size="22" /></span><div><div style="font-family:var(--font-head);font-weight:700;font-size:15px">{{ $a }}</div><div class="muted" style="font-size:13px">{{ $b }}</div></div></div>@endforeach</div></section>

    {{-- TRENDING --}}
    @include('frontend.partials.product-section', ['eyebrow' => 'Heating up', 'title' => 'Trending now', 'items' => $trend])

    {{-- REVIEWS --}}
    <section class="ut-wrap" style="margin-top:72px">
        <div class="ut-sec-head ut-testimonial-head">
            <div><span class="ut-eyebrow">Loved by 50,000+</span><h2 style="margin-top:8px">What the community says</h2></div>
            <div class="ut-testimonial-controls" aria-label="Review slider controls"><button type="button" data-testimonial-action="prev" aria-label="Previous reviews"><x-frontend.icon n="arrowL" :size="18" /></button><button type="button" data-testimonial-action="next" aria-label="Next reviews"><x-frontend.icon n="arrowR" :size="18" /></button></div>
        </div>
        <div class="ut-testimonial-slider" aria-roledescription="carousel" aria-label="Customer reviews">
            <div class="ut-testimonial-track">
                @foreach($reviews as $r)
                    <article class="ut-card ut-testimonial-card">
                        <div class="ut-row" style="justify-content:space-between"><x-frontend.stars :value="$r['rating']" /><span class="ut-tag ut-tag-success"><x-frontend.icon n="checkC" :size="12" style="vertical-align:-1px" /> Verified</span></div>
                        <p>&quot;{{ $r['text'] }}&quot;</p>
                        <div class="ut-row" style="gap:11px;margin-top:auto"><span class="ut-testimonial-avatar">{{ $r['name'][0] }}</span><div><div class="ut-testimonial-name">{{ $r['name'] }}</div><div class="muted" style="font-size:12.5px">{{ $r['city'] }}</div></div></div>
                    </article>
                @endforeach
            </div>
        </div>
        <div class="ut-testimonial-dots" aria-label="Review slides">@foreach($reviews as $index => $review)<button type="button" data-testimonial-dot="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}" aria-label="Show review {{ $index + 1 }}"></button>@endforeach</div>
    </section>

    {{-- INSTAGRAM --}}
    <section class="ut-wrap" style="margin-top:72px"><div class="ut-sec-head"><div><span class="ut-eyebrow">@tshirtshop</span><h2 style="margin-top:8px">Tag us to be featured</h2></div><a href="#" class="ut-link">Follow <x-frontend.icon n="arrowR" :size="16" /></a></div><div class="ut-insta">@foreach(['#e7e9ee,#cfd4dd', '#26282d,#3b3e46', '#e7ddcf,#cdb79a', '#dde6ee,#b9cad9', '#e6e0d6,#c9bba2', '#e3e8ec,#c2cdd6'] as $i => $g)<div class="ut-insta-cell"><x-frontend.ph tint="linear-gradient(150deg,{{ $g }})" :dark="$i === 1" style="aspect-ratio:1" /><span class="ut-insta-ic"><x-frontend.icon n="ig" :size="26" /></span></div>@endforeach</div></section>

    {{-- NEWSLETTER --}}
    <section class="ut-wrap" style="margin-top:72px"><div style="background:linear-gradient(135deg,var(--blue),#1e40af);border-radius:var(--r-xl);padding:clamp(32px,5vw,56px);color:#fff;text-align:center;position:relative;overflow:hidden"><div style="position:absolute;bottom:-80px;left:-40px;width:260px;height:260px;border-radius:50%;background:rgba(255,255,255,.08)"></div><span class="ut-eyebrow" style="color:rgba(255,255,255,.8)">Members get more</span><h2 style="color:#fff;font-size:clamp(28px,3.4vw,42px);margin:10px 0 12px;position:relative">Get 10% off your first order</h2><p style="opacity:.85;max-width:460px;margin:0 auto 26px;font-size:16px">Early access to drops, members-only pricing, and free shipping. No spam — just good tees.</p><form onsubmit="event.preventDefault(); this.innerHTML='<div style=&quot;font-family:var(--font-head);font-weight:600&quot;>✓ You\'re in — check your inbox!</div>';" style="display:flex;gap:10px;max-width:460px;margin:0 auto;position:relative"><input class="ut-input" type="email" placeholder="you@email.com" required style="border:0;flex:1"><button class="ut-btn ut-btn-ink" type="submit" style="background:var(--ink)">Subscribe</button></form></div></section>

</div>
@endsection
