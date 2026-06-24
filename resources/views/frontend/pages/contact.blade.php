@extends('frontend.layouts.frontend')
@section('title', 'Contact — T-Shirt Shop')

@push('head')
<style>.ut-contact-grid{ display:grid; grid-template-columns:1fr 1.3fr; gap:40px; align-items:start; } @media (max-width:767px){ .ut-contact-grid{ grid-template-columns:1fr; } }</style>
@endpush

@section('content')
<div class="anim-up">
    <section style="background:#fff;border-bottom:1px solid var(--border)">
        <div class="ut-wrap" style="padding:clamp(40px,6vw,72px) 24px">
            <span class="ut-eyebrow">We're here to help</span>
            <h1 style="font-size:clamp(34px,5vw,60px);line-height:1;margin:14px 0 14px;max-width:760px">Get in touch</h1>
            <p style="font-size:18px;max-width:560px" class="muted">Questions about sizing, an order, or just want to say hi? We'd love to hear from you.</p>
        </div>
    </section>

    <section class="ut-wrap" style="margin-top:48px">
        <div class="ut-contact-grid">
            <div class="ut-col" style="gap:14px">
                @foreach([['mail', 'Email us', 'help@tshirtshop.com', 'We reply within 24 hours'], ['truck', 'Order support', 'Track or manage orders', 'Mon–Fri, 9am–6pm ET'], ['ig', 'Social', '@tshirtshop', 'DMs always open']] as [$ic, $t, $v, $d])
                    <div class="ut-card" style="padding:22px;display:flex;gap:16px;align-items:center">
                        <span style="width:48px;height:48px;border-radius:14px;background:var(--bg);display:grid;place-items:center;color:var(--blue);flex-shrink:0"><x-frontend.icon :n="$ic" :size="22" /></span>
                        <div><div style="font-family:var(--font-head);font-weight:700;font-size:14px">{{ $t }}</div><div style="font-family:var(--font-head);font-weight:600;font-size:15px;color:var(--blue)">{{ $v }}</div><div class="muted" style="font-size:12.5px">{{ $d }}</div></div>
                    </div>
                @endforeach
                <div class="ut-card" style="padding:0;overflow:hidden">
                    <x-frontend.ph tint="linear-gradient(150deg,#dde6ee,#b9cad9)" label="store map" style="aspect-ratio:16/9" />
                    <div style="padding:18px"><div class="ut-row" style="gap:10px"><span style="color:var(--accent)"><x-frontend.icon n="pin" :size="18" /></span><div><div style="font-family:var(--font-head);font-weight:700;font-size:14px">Flagship store</div><div class="muted" style="font-size:13px">211 Wythe Ave, Brooklyn, NY</div></div></div></div>
                </div>
            </div>

            <div class="ut-card" style="padding:clamp(24px,4vw,34px)" id="contactCard">
                <h2 style="font-size:22px;margin-bottom:20px">Send us a message</h2>
                <form class="ut-col" style="gap:16px" onsubmit="return submitContact(event)">
                    <div class="ut-form-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="field"><label>Name</label><input class="ut-input" placeholder="Alex Rivera" required></div>
                        <div class="field"><label>Email</label><input class="ut-input" type="email" placeholder="you@email.com" required></div>
                    </div>
                    <div class="field"><label>Subject</label>
                        <select class="ut-input">
                            <option value="" disabled selected>Choose a topic…</option>
                            <option>Order or shipping</option><option>Returns or exchange</option><option>Product or sizing</option><option>Something else</option>
                        </select>
                    </div>
                    <div class="field"><label>Message</label><textarea class="ut-input" rows="5" placeholder="How can we help?" required></textarea></div>
                    <button class="ut-btn ut-btn-accent ut-btn-block ut-btn-lg" type="submit">Send message <x-frontend.icon n="arrowR" :size="17" /></button>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    function submitContact(e){
        e.preventDefault();
        document.getElementById('contactCard').innerHTML =
            '<div style="text-align:center;padding:30px 0"><div style="width:70px;height:70px;border-radius:50%;background:#dcfce7;color:#15803d;display:grid;place-items:center;margin:0 auto 18px"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></div>'+
            '<h2 style="font-size:24px">Message sent!</h2><p class="muted" style="margin-top:8px">We\'ll get back to you within 24 hours.</p></div>';
        return false;
    }
</script>
@endpush

