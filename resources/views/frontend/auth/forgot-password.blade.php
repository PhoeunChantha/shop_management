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
            <form class="ut-col" style="gap:16px" action="{{ route('frontend.password.email') }}" method="POST">
                @csrf
                <div class="field"><label>Email address</label><input class="ut-input" type="email" name="email" value="{{ old('email') }}" placeholder="you@email.com" autofocus required>
                    @error('email')<span style="color:var(--accent);font-size:12.5px;margin-top:6px;display:block">{{ $message }}</span>@enderror
                </div>
                <button class="ut-btn ut-btn-ink ut-btn-block ut-btn-lg" type="submit">Send reset link</button>
            </form>
            <p class="muted" style="text-align:center;margin-top:26px;font-size:14px">Remembered it? <a href="{{ route('frontend.login') }}" style="color:var(--blue);font-weight:600;font-family:var(--font-head)">Back to sign in</a></p>
        </div>
    </div>
</div>
@endsection



