@extends('frontend.layouts.frontend')
@section('title', 'FAQ — T-Shirt Shop')

@section('content')
@php $cats = collect($faq)->groupBy('cat'); @endphp
<div class="anim-up">
    <section style="background:#fff;border-bottom:1px solid var(--border)">
        <div class="ut-wrap" style="padding:clamp(40px,6vw,72px) 24px">
            <span class="ut-eyebrow">Help center</span>
            <h1 style="font-size:clamp(34px,5vw,60px);line-height:1;margin:14px 0 14px;max-width:760px">Frequently asked questions</h1>
            <p style="font-size:18px;max-width:560px" class="muted">Find quick answers about orders, shipping, returns, and more.</p>
        </div>
    </section>

    <section class="ut-wrap" style="margin-top:40px;max-width:820px">
        <div style="position:relative;margin-bottom:30px">
            <span style="position:absolute;left:16px;top:15px;color:var(--text-2)"><x-frontend.icon n="search" :size="19" /></span>
            <input class="ut-input" id="faqSearch" placeholder="Search for an answer…" style="padding:14px 16px 14px 46px;border-radius:var(--r-pill)" oninput="faqFilter(this.value)">
        </div>
        <div id="faqNoResults" style="display:none" class="ut-card"><div style="padding:50px;text-align:center"><h3>No results</h3><p class="muted" style="margin-top:6px">Try a different search or contact us.</p></div></div>

        @foreach($cats as $cat => $items)
            <div class="faq-group" data-cat="{{ $cat }}" style="margin-bottom:28px">
                <h3 style="font-size:15px;text-transform:uppercase;letter-spacing:.06em;color:var(--text-2);margin-bottom:12px">{{ $cat }}</h3>
                <div class="ut-card" style="padding:4px">
                    @foreach($items as $f)
                        <div class="faq-item" data-text="{{ strtolower($f['q'].' '.$f['a']) }}" style="border-bottom:1px solid var(--border-2)">
                            <button type="button" class="ut-acc-q" aria-expanded="false">
                                {{ $f['q'] }}<span class="chev"><x-frontend.icon n="chevD" :size="20" /></span>
                            </button>
                            <p class="ut-acc-a muted" style="display:none">{{ $f['a'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="ut-card" style="padding:28px;text-align:center;margin-top:8px">
            <h3 style="font-size:20px">Still need help?</h3>
            <p class="muted" style="margin:8px 0 18px">Our team is here for you.</p>
            <a href="{{ route('frontend.pages.contact') }}" class="ut-btn ut-btn-ink">Contact support</a>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    function faqFilter(q){
        q = q.toLowerCase(); var anyShown = false;
        document.querySelectorAll('.faq-group').forEach(function(g){
            var groupShown = false;
            g.querySelectorAll('.faq-item').forEach(function(it){
                var ok = !q || it.dataset.text.indexOf(q) > -1;
                it.style.display = ok ? '' : 'none';
                if(ok){ groupShown = true; anyShown = true; }
            });
            g.style.display = groupShown ? '' : 'none';
        });
        document.getElementById('faqNoResults').style.display = anyShown ? 'none' : '';
    }
</script>
@endpush


