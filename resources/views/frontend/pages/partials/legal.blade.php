{{-- Shared legal layout. Pass $title, $updated, $sections (array of ['h'=>, 'p'=>[]]) --}}
@extends('frontend.layouts.frontend')
@section('title', $title.' — T-Shirt Shop')

@push('head')
<style>.ut-legal-grid{ display:grid; grid-template-columns:240px 1fr; gap:48px; align-items:start; } @media (max-width:767px){ .ut-legal-grid{ grid-template-columns:1fr; } .ut-legal-toc{ display:none; } }</style>
@endpush

@section('content')
<div class="anim-up">
    <section style="background:#fff;border-bottom:1px solid var(--border)">
        <div class="ut-wrap" style="padding:clamp(40px,6vw,72px) 24px">
            <span class="ut-eyebrow">Legal</span>
            <h1 style="font-size:clamp(34px,5vw,60px);line-height:1;margin:14px 0 14px;max-width:760px">{{ $title }}</h1>
            <p style="font-size:18px;max-width:560px" class="muted">Last updated {{ $updated }}</p>
        </div>
    </section>

    <section class="ut-wrap" style="margin-top:40px">
        <div class="ut-legal-grid">
            <aside class="ut-legal-toc ut-card" style="padding:14px;position:sticky;top:96px">
                <div style="font-family:var(--font-head);font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--text-2);padding:6px 10px 10px">On this page</div>
                @foreach($sections as $i => $s)
                    <a href="#sec-{{ $i }}" style="display:block;padding:9px 10px;border-radius:10px;font-family:var(--font-head);font-weight:500;font-size:13.5px;color:var(--text-2)">{{ $i + 1 }}. {{ $s['h'] }}</a>
                @endforeach
            </aside>
            <div style="max-width:720px">
                @foreach($sections as $i => $s)
                    <div id="sec-{{ $i }}" style="margin-bottom:36px;scroll-margin-top:90px">
                        <h2 style="font-size:22px;margin-bottom:12px">{{ $i + 1 }}. {{ $s['h'] }}</h2>
                        @foreach($s['p'] as $para)
                            <p class="muted" style="font-size:15px;line-height:1.75;margin-bottom:12px">{{ $para }}</p>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
@endsection

