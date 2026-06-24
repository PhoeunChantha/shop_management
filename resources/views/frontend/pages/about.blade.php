@extends('frontend.layouts.frontend')
@section('title', 'About — T-Shirt Shop')

@push('head')
<style>.ut-about-grid{ display:grid; grid-template-columns:1fr 1fr; gap:48px; align-items:center; } @media (max-width:767px){ .ut-about-grid{ grid-template-columns:1fr; } }</style>
@endpush

@section('content')
<div class="anim-up">
    {{-- hero --}}
    <section style="background:var(--ink);color:#fff;border-bottom:1px solid var(--border)">
        <div class="ut-wrap" style="padding:clamp(40px,6vw,72px) 24px">
            <span class="ut-eyebrow" style="color:rgba(255,255,255,.8)">Our story</span>
            <h1 style="color:#fff;font-size:clamp(34px,5vw,60px);line-height:1;margin:14px 0 14px;max-width:760px">Heavyweight essentials, done right.</h1>
            <p style="font-size:18px;max-width:560px;color:rgba(255,255,255,.75)">T-Shirt Shop started with one frustration: every great tee either fell apart or cost a fortune. So we made our own.</p>
        </div>
    </section>

    <section class="ut-wrap" style="margin-top:56px">
        <div class="ut-about-grid">
            <x-frontend.ph tint="linear-gradient(150deg,#e7e9ee,#cfd4dd)" label="studio / brand image" style="aspect-ratio:4/3;border-radius:var(--r-xl)" />
            <div>
                <span class="ut-eyebrow">Est. 2024</span>
                <h2 style="font-size:clamp(26px,3vw,38px);margin:12px 0 16px">From a studio in Brooklyn to 50,000+ closets.</h2>
                <p class="muted" style="font-size:16px;line-height:1.7;margin-bottom:16px">We obsess over the details most brands skip — the weight of the fabric, the drape of the shoulder, the way a collar holds up after a hundred washes. Every T-Shirt Shop piece is a small rebellion against disposable fashion.</p>
                <p class="muted" style="font-size:16px;line-height:1.7">Today we're a team of designers, makers, and wearers building the wardrobe staples we always wanted.</p>
            </div>
        </div>
    </section>

    <section class="ut-wrap" style="margin-top:64px">
        <div style="background:var(--ink);border-radius:var(--r-xl);padding:clamp(28px,4vw,44px);color:#fff">
            <div class="ut-stat-grid">
                @foreach([['50k+', 'Happy customers'], ['240gsm', 'Signature weight'], ['4.9★', 'Average rating'], ['100%', 'Organic cotton']] as [$a, $b])
                    <div style="text-align:center"><div style="font-family:var(--font-head);font-weight:800;font-size:clamp(28px,3vw,40px)">{{ $a }}</div><div style="opacity:.7;font-size:14px;margin-top:4px">{{ $b }}</div></div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="ut-wrap" style="margin-top:64px">
        <div class="ut-sec-head"><div><span class="ut-eyebrow">What we stand for</span><h2 style="margin-top:8px">Our values</h2></div></div>
        <div class="ut-trust" style="grid-template-columns:repeat(3,1fr)">
            @foreach([['shield', 'Built to last', 'Heavyweight 240gsm cotton, triple-stitched seams, and a fit engineered to hold its shape for years — not seasons.'], ['spark', 'Made responsibly', 'Organic, GOTS-certified cotton and carbon-neutral delivery on every order. Premium without the planetary cost.'], ['truck', 'Direct to you', 'No middlemen, no markups. We design in-studio and ship straight to your door at a fair price.']] as [$ic, $t, $d])
                <div class="ut-card" style="padding:26px">
                    <span style="width:50px;height:50px;border-radius:14px;background:var(--bg);display:grid;place-items:center;color:var(--blue);margin-bottom:16px"><x-frontend.icon :n="$ic" :size="24" /></span>
                    <h3 style="font-size:19px;margin-bottom:8px">{{ $t }}</h3>
                    <p class="muted" style="font-size:14.5px;line-height:1.6">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="ut-wrap" style="margin-top:64px">
        <div style="text-align:center;background:#fff;border:1px solid var(--border-2);border-radius:var(--r-xl);padding:clamp(36px,5vw,56px);box-shadow:var(--sh-1)">
            <h2 style="font-size:clamp(26px,3vw,38px);margin-bottom:12px">Ready to feel the difference?</h2>
            <p class="muted" style="font-size:16px;margin-bottom:26px">Find your new favorite tee.</p>
            <a href="{{ route('frontend.shop.index') }}" class="ut-btn ut-btn-accent ut-btn-lg">Shop the collection <x-frontend.icon n="arrowR" :size="18" /></a>
        </div>
    </section>
</div>
@endsection


