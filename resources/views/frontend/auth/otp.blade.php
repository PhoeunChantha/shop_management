@extends('frontend.layouts.frontend')
@section('title', 'Verify — T-Shirt Shop')
@php $bareLayout = true; @endphp

@section('content')
<div class="ut-auth">
    @include('frontend.auth.partials.brand')
    <div class="ut-auth-form">
        <a href="{{ route('frontend.home') }}" class="ut-link ut-hide-mobile" style="position:absolute;top:28px;right:32px;font-size:13.5px"><x-frontend.icon n="arrowL" :size="15" /> Back to store</a>
        <div style="width:100%;max-width:400px;margin:0 auto">
            <h1 style="font-size:clamp(28px,3vw,34px);margin-bottom:8px">Verify your email</h1>
            <p class="muted" style="margin-bottom:28px;font-size:15px">We sent a 6-digit code to alex@email.com. Enter it below to continue.</p>
            <div class="ut-row" id="otpGroup" style="gap:10px;justify-content:space-between;margin-bottom:22px">
                @for($i = 0; $i < 6; $i++)
                    <input class="ut-otp-box" inputmode="numeric" maxlength="1" aria-label="Digit {{ $i + 1 }}">
                @endfor
            </div>
            <a href="{{ route('frontend.account.dashboard') }}" class="ut-btn ut-btn-ink ut-btn-block ut-btn-lg" id="otpVerify" style="opacity:.5;pointer-events:none">Verify &amp; continue</a>
            <p class="muted" style="text-align:center;margin-top:18px;font-size:13.5px">Resend code in <b class="mono" id="otpResend" style="color:var(--ink)">0:38</b></p>
            <p class="muted" style="text-align:center;margin-top:18px;font-size:14px">Wrong email? <a href="{{ route('frontend.register') }}" style="color:var(--blue);font-weight:600;font-family:var(--font-head)">Go back</a></p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // enable verify button + un-mute once all digits entered (handled in main.js otp block;
    // here we also flip pointer-events when enabled)
    document.getElementById('otpGroup').addEventListener('input', function(){
        var btn = document.getElementById('otpVerify');
        var ok = [].slice.call(this.querySelectorAll('input')).every(function(b){ return b.value; });
        btn.style.opacity = ok ? 1 : .5;
        btn.style.pointerEvents = ok ? 'auto' : 'none';
    });
</script>
@endpush


