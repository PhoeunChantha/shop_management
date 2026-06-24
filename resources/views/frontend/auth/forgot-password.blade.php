@extends('frontend.layouts.frontend')
@section('title', 'Forgot Password — T-Shirt Shop')
@php $bareLayout = true; @endphp

@section('content')
<div class="ut-auth">
    @include('frontend.auth.partials.brand')
    <div class="ut-auth-form">
        <a href="{{ route('frontend.home') }}" class="ut-link ut-hide-mobile" style="position:absolute;top:28px;right:32px;font-size:13.5px"><x-frontend.icon n="arrowL" :size="15" /> Back to store</a>
        <div style="width:100%;max-width:400px;margin:0 auto" id="forgotWrap">
            <h1 style="font-size:clamp(28px,3vw,34px);margin-bottom:8px">Forgot password?</h1>
            <p class="muted" style="margin-bottom:28px;font-size:15px">No worries — enter your email and we'll send a reset link.</p>
            <form class="ut-col" style="gap:16px" onsubmit="event.preventDefault(); showSent(this.querySelector('input').value);">
                <div class="field"><label>Email address</label><input class="ut-input" type="email" placeholder="you@email.com" autofocus required></div>
                <button class="ut-btn ut-btn-ink ut-btn-block ut-btn-lg" type="submit">Send reset link</button>
            </form>
            <p class="muted" style="text-align:center;margin-top:26px;font-size:14px">Remembered it? <a href="{{ route('frontend.login') }}" style="color:var(--blue);font-weight:600;font-family:var(--font-head)">Back to sign in</a></p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function showSent(email){
        document.getElementById('forgotWrap').innerHTML =
            '<h1 style="font-size:clamp(28px,3vw,34px);margin-bottom:8px">Check your email</h1>'+
            '<p class="muted" style="margin-bottom:24px;font-size:15px">We sent a reset link to <b style="color:var(--ink)">'+(email||'your inbox')+'</b>.</p>'+
            '<div style="width:64px;height:64px;border-radius:18px;background:#dbeafe;color:var(--blue);display:grid;place-items:center;margin-bottom:16px"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m4 7 8 6 8-6"/></svg></div>'+
            '<p class="muted" style="font-size:14.5px;line-height:1.6;margin-bottom:18px">Click the link in the email to reset your password. If it doesn\'t arrive in a few minutes, check your spam folder.</p>'+
            '<a href="{{ route('frontend.password.reset') }}" class="ut-btn ut-btn-ink ut-btn-block ut-btn-lg">Open reset link</a>'+
            '<a href="{{ route('frontend.login') }}" class="ut-btn ut-btn-ghost ut-btn-block" style="margin-top:10px">Back to sign in</a>';
    }
</script>
@endpush


