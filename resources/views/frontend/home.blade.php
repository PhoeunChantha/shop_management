@extends('frontend.layouts.frontend')
@section('title', 'T-Shirt Shop — Premium Streetwear Tees')

@push('head')
    <style>
        .ut-coll-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            grid-template-rows: repeat(2, 1fr);
            gap: 18px;
            height: 460px;
        }

        @media (max-width:1024px) {
            .ut-coll-grid {
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: auto;
                height: auto;
            }

            .ut-coll-grid>* {
                grid-column: auto !important;
                grid-row: auto !important;
                aspect-ratio: 4/3;
            }
        }

        @media (max-width:767px) {
            .ut-coll-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="anim-up">

        {{-- HERO SLIDER — fully data-driven. Edit the $heroSlides array below to add /
             reorder slides, or pass $heroSlides (e.g. DB banners) from a controller to
             override it. Each slide: image, kicker, title, copy, primary, secondary, trust. --}}
        @php
            $heroSlides = $heroSlides ?? [
                ['kicker' => '01 / New arrivals', 'title' => 'Premium oversized<br>t-shirts.', 'copy' => 'Built for comfort. Designed for style. Garment-dyed heavyweight cotton with the relaxed structure you reach for every day.', 'primary' => 'Shop new arrivals', 'secondary' => 'Explore the edit', 'trust' => '240gsm organic cotton', 'image' => 'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?auto=format&fit=crop&w=1800&q=88'],
                ['kicker' => '02 / Best sellers', 'title' => 'The tees<br>everyone loves.', 'copy' => 'The most-reordered fits in our collection. Proven weight, precise proportions, and color that only gets better with time.', 'primary' => 'Shop best sellers', 'secondary' => 'See the reviews', 'trust' => '12k+ five-star reviews', 'image' => 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&w=1800&q=88'],
                ['kicker' => '03 / Graphic collection', 'title' => 'Bold designs.<br>Everyday wear.', 'copy' => 'Limited-run artwork meets our signature heavyweight base. Made to be noticed, built to stay in rotation.', 'primary' => 'Shop graphic tees', 'secondary' => 'View lookbook', 'trust' => 'Limited edition print runs', 'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1800&q=88'],
                ['kicker' => '04 / Flash sale', 'title' => 'Up to 40% off.<br>Last call.', 'copy' => 'Final sizes from past drops. Once a color or size is gone, it is gone. Move fast on the pieces you missed.', 'primary' => 'Shop the sale', 'secondary' => 'View all offers', 'trust' => 'Ends Sunday at midnight', 'image' => 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=1800&q=88'],
            ];
        @endphp
        <section class="ut-hero" aria-label="Featured collections" aria-roledescription="carousel"
            style="display:block;height:590px;min-height:590px">
            <div id="utHeroCarousel" class="ut-hero-carousel" style="display:block;height:590px;min-height:590px">
                <div class="ut-hero-dots" role="tablist" aria-label="Choose hero slide">
                    @foreach ($heroSlides as $i => $s)
                        <button type="button" data-hero-slide="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}"
                            @if ($i === 0) aria-current="true" @endif
                            aria-label="Show slide {{ $i + 1 }}: {{ strip_tags($s['kicker']) }}"><i></i></button>
                    @endforeach
                </div>
                <div class="ut-hero-track">
                    @foreach ($heroSlides as $index => $slide)
                        <div class="ut-hero-item {{ $index === 0 ? 'active' : '' }}" aria-roledescription="slide"
                            aria-label="{{ $index + 1 }} of {{ count($heroSlides) }}">
                            <div class="ut-hero-slide">
                                <div class="ut-hero-media" role="img"
                                    aria-label="{{ strip_tags($slide['kicker']) }}"
                                    style="background-image:url('{{ $slide['image'] }}')"></div>
                                <div class="ut-wrap ut-hero-layout">
                                    <div class="ut-hero-copy"><span class="ut-hero-kicker">{{ $slide['kicker'] }}</span>
                                        <h1>{!! $slide['title'] !!}</h1>
                                        <p>{{ $slide['copy'] }}</p>
                                        <div class="ut-hero-ctas"><a href="{{ route('frontend.shop.index') }}"
                                                class="ut-btn ut-btn-accent ut-btn-lg">{{ $slide['primary'] }}
                                                <x-frontend.icon n="arrowR" :size="18" /></a><a
                                                href="{{ route('frontend.shop.index') }}"
                                                class="ut-btn ut-hero-secondary">{{ $slide['secondary'] }}</a></div>
                                    </div>
                                    <div class="ut-hero-product" aria-hidden="true">
                                        <div class="ut-hero-product-card">
                                            <span>{{ $slide['trust'] }}</span><b>URBAN<br>THREAD</b></div>
                                    </div>
                                </div>
                                <div class="ut-wrap">
                                    <div class="ut-hero-trust"><span><b>4.9</b> rating</span><i></i><span><b>12k+</b>
                                            reviews</span><i></i><span><b>240gsm</b> organic
                                            cotton</span><i></i><span><b>30-day</b> returns</span><i></i><span><b>Free</b>
                                            shipping $75+</span></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button class="ut-hero-arrow ut-hero-arrow-prev" type="button" data-hero-action="prev"
                    aria-label="Previous slide"><x-frontend.icon n="arrowL" :size="22" /></button><button
                    class="ut-hero-arrow ut-hero-arrow-next" type="button" data-hero-action="next"
                    aria-label="Next slide"><x-frontend.icon n="arrowR" :size="22" /></button>
            </div>
        </section>

        {{-- COLLECTIONS --}}
        <section class="ut-wrap" style="margin-top:96px">
            <div class="ut-sec-head" data-reveal>
                <div><span class="ut-eyebrow">Curated</span>
                    <h2 style="margin-top:14px">Featured collections</h2>
                </div>
                <a href="{{ route('frontend.shop.index') }}" class="ut-link">All collections <x-frontend.icon n="arrowR"
                        :size="16" /></a>
            </div>
            <div class="ut-coll-grid">
                @foreach ($collections as $i => $c)
                    <a href="{{ route('frontend.shop.index') }}" class="ut-coll-cell" data-reveal
                        style="position:relative;border-radius:var(--r-lg);overflow:hidden;{{ $i === 0 ? 'grid-row:span 2;' : '' }}">
                        <x-frontend.ph :tint="$c['tint']" :dark="$c['dark']" style="position:absolute;inset:0" />
                        <div
                            style="position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(0,0,0,.5))">
                        </div>
                        <div
                            style="position:absolute;left:0;right:0;bottom:0;padding:{{ $i === 0 ? '28px' : '18px' }};color:#fff">
                            <div
                                style="font-family:var(--font-head);font-weight:700;font-size:{{ $i === 0 ? '28px' : '18px' }}">
                                {{ $c['name'] }}</div>
                            <div style="opacity:.8;font-size:13px;margin-top:2px">{{ $c['sub'] }} ·
                                {{ $c['count'] }} styles</div>
                        </div>
                        <span class="icon-btn"
                            style="position:absolute;top:14px;right:14px;background:#fff"><x-frontend.icon n="arrowR"
                                :size="18" /></span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- BEST SELLERS --}}
        @include('frontend.partials.product-section', [
            'eyebrow' => 'Most wanted',
            'title' => 'Best sellers',
            'items' => $best,
        ])

        {{-- FLASH SALE --}}
        <section class="ut-wrap" style="margin-top:96px">
            <div data-reveal
                style="background:var(--ink);border-radius:var(--r-xl);padding:clamp(24px,4vw,44px);color:#fff;position:relative;overflow:hidden"
                data-countdown="{{ 7 * 3600 + 42 * 60 + 18 }}">
                <div
                    style="position:absolute;top:-60px;right:-40px;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,rgba(122,100,70,.55),transparent 70%)">
                </div>
                <div class="ut-row"
                    style="justify-content:space-between;flex-wrap:wrap;gap:18px;margin-bottom:28px;position:relative">
                    <div><span class="ut-eyebrow" style="color:var(--accent)"><x-frontend.icon n="flame" :size="13"
                                style="vertical-align:-2px" /> Flash sale</span>
                        <h2 style="color:#fff;font-size:clamp(26px,3vw,38px);margin-top:8px">Up to 40% off — ends soon</h2>
                    </div>
                    <div class="ut-row" style="gap:10px">
                        @foreach ([['data-h', 'hrs'], ['data-m', 'min'], ['data-s', 'sec']] as $i => [$attr, $lab])
                            <div
                                style="text-align:center;background:rgba(255,255,255,.1);border-radius:14px;padding:12px 14px;min-width:64px">
                                <div class="mono" style="font-size:28px;font-weight:700;font-variant-numeric:tabular-nums"
                                    {{ $attr }}>00</div>
                                <div style="font-size:11px;opacity:.65;text-transform:uppercase;letter-spacing:.1em">
                                    {{ $lab }}</div>
                            </div>
                            @if ($i < 2)
                                <span style="font-size:26px;opacity:.5">:</span>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="ut-rail" style="position:relative">
                    @foreach ($flash as $p)
                        <div style="background:#fff;border-radius:var(--r-lg);overflow:hidden"><x-frontend.product-card
                                :product="$p" /></div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- NEW ARRIVALS --}}
        @include('frontend.partials.product-section', [
            'eyebrow' => 'Just dropped',
            'title' => 'New arrivals',
            'items' => $fresh,
        ])

        {{-- TRUST BAR --}}
        <section class="ut-wrap" style="margin-top:96px">
            <div class="ut-trust" data-reveal
                style="background:var(--paper);border:1px solid var(--border-2);border-radius:var(--r-lg);padding:30px;box-shadow:var(--sh-1)">
                @foreach ([['truck', 'Free shipping', 'On orders over $75'], ['refresh', '30-day returns', 'No-questions-asked'], ['shield', 'Secure checkout', '256-bit encryption'], ['spark', 'Carbon neutral', 'Every delivery']] as [$ic, $a, $b])
                    <div class="ut-row" style="gap:14px"><span
                            style="width:48px;height:48px;border-radius:var(--r-sm);background:var(--bg);display:grid;place-items:center;color:var(--accent);flex-shrink:0"><x-frontend.icon
                                :n="$ic" :size="22" /></span>
                        <div>
                            <div style="font-family:var(--font-head);font-weight:600;font-size:16px">{{ $a }}
                            </div>
                            <div class="muted" style="font-size:13px">{{ $b }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- TRENDING --}}
        @include('frontend.partials.product-section', [
            'eyebrow' => 'Heating up',
            'title' => 'Trending now',
            'items' => $trend,
        ])

        {{-- REVIEWS --}}
        <section class="ut-wrap" style="margin-top:96px">
            <div class="ut-sec-head ut-testimonial-head" data-reveal>
                <div><span class="ut-eyebrow">Loved by 50,000+</span>
                    <h2 style="margin-top:14px">What the community says</h2>
                </div>
                <div class="ut-testimonial-controls" aria-label="Review slider controls"><button type="button"
                        data-testimonial-action="prev" aria-label="Previous reviews"><x-frontend.icon n="arrowL"
                            :size="18" /></button><button type="button" data-testimonial-action="next"
                        aria-label="Next reviews"><x-frontend.icon n="arrowR" :size="18" /></button></div>
            </div>
            <div class="ut-testimonial-slider" aria-roledescription="carousel" aria-label="Customer reviews">
                <div class="ut-testimonial-track">
                    @foreach ($reviews as $r)
                        <article class="ut-card ut-testimonial-card">
                            <div class="ut-row" style="justify-content:space-between"><x-frontend.stars
                                    :value="$r['rating']" /><span class="ut-tag ut-tag-success"><x-frontend.icon n="checkC"
                                        :size="12" style="vertical-align:-1px" /> Verified</span></div>
                            <p>&quot;{{ $r['text'] }}&quot;</p>
                            <div class="ut-row" style="gap:11px;margin-top:auto"><span
                                    class="ut-testimonial-avatar">{{ $r['name'][0] }}</span>
                                <div>
                                    <div class="ut-testimonial-name">{{ $r['name'] }}</div>
                                    <div class="muted" style="font-size:12.5px">{{ $r['city'] }}</div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
            <div class="ut-testimonial-dots" aria-label="Review slides">
                @foreach ($reviews as $index => $review)
                    <button type="button" data-testimonial-dot="{{ $index }}"
                        class="{{ $index === 0 ? 'active' : '' }}"
                        aria-label="Show review {{ $index + 1 }}"></button>
                @endforeach
            </div>
        </section>

        {{-- INSTAGRAM --}}
        <section class="ut-wrap" style="margin-top:96px">
            <div class="ut-sec-head" data-reveal>
                <div><span class="ut-eyebrow">@tshirtshop</span>
                    <h2 style="margin-top:14px">Tag us to be featured</h2>
                </div><a href="#" class="ut-link">Follow <x-frontend.icon n="arrowR" :size="16" /></a>
            </div>
            <div class="ut-insta">
                @foreach (['#ece6da,#d8cdb8', '#23201a,#3a352b', '#e7ddcf,#cdb79a', '#e3ddd0,#c4b69c', '#e6e0d6,#c9bba2', '#ded7c8,#bcae93'] as $i => $g)
                    <div class="ut-insta-cell" data-reveal><x-frontend.ph
                            tint="linear-gradient(150deg,{{ $g }})" :dark="$i === 1"
                            style="aspect-ratio:1" /><span class="ut-insta-ic"><x-frontend.icon n="ig"
                                :size="26" /></span></div>
                @endforeach
            </div>
        </section>

        {{-- NEWSLETTER --}}
        <section class="ut-wrap" style="margin-top:96px">
            <div data-reveal
                style="background:linear-gradient(135deg,#1d1a14,#100e0b);border-radius:var(--r-xl);padding:clamp(36px,5vw,64px);color:#fff;text-align:center;position:relative;overflow:hidden">
                <div
                    style="position:absolute;bottom:-80px;left:-40px;width:260px;height:260px;border-radius:50%;background:rgba(122,100,70,.22)">
                </div><span class="ut-eyebrow" style="color:#cbb393">Members get more</span>
                <h2
                    style="color:#fff;font-weight:400;font-size:clamp(30px,3.8vw,46px);margin:14px 0 14px;position:relative">
                    Get 10% off your first order</h2>
                <p style="opacity:.85;max-width:460px;margin:0 auto 26px;font-size:16px">Early access to drops,
                    members-only pricing, and free shipping. No spam — just good tees.</p>
                <form
                    onsubmit="event.preventDefault(); this.innerHTML='<div style=&quot;font-family:var(--font-head);font-weight:600&quot;>✓ You\'re in — check your inbox!</div>';"
                    style="display:flex;gap:10px;max-width:460px;margin:0 auto;position:relative"><input class="ut-input"
                        type="email" placeholder="you@email.com" required style="border:0;flex:1"><button
                        class="ut-btn ut-btn-ink" type="submit" style="background:var(--ink)">Subscribe</button></form>
            </div>
        </section>

    </div>
@endsection
