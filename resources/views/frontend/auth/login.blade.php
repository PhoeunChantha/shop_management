@extends('frontend.layouts.frontend')
@section('title', 'Sign In — T-Shirt Shop')
@php $bareLayout = true; @endphp

@section('content')
<div class="ut-auth">
    @include('frontend.auth.partials.brand')
    <div class="ut-auth-form">
        <a href="{{ route('frontend.home') }}" class="ut-link ut-hide-mobile" style="position:absolute;top:28px;right:32px;font-size:13.5px"><x-frontend.icon n="arrowL" :size="15" /> Back to store</a>
        <div style="width:100%;max-width:400px;margin:0 auto">
            <h1 style="font-size:clamp(28px,3vw,34px);margin-bottom:8px">Welcome back</h1>
            <p class="muted" style="margin-bottom:28px;font-size:15px">Sign in to your account to continue shopping.</p>

            {{-- social --}}
            <div class="ut-row" style="gap:12px">
                <button type="button" class="ut-btn ut-btn-ghost" style="flex:1" onclick="utToast('Continue with Apple')"><x-frontend.icon n="lock" :size="18" /> Apple</button>
                <button type="button" class="ut-btn ut-btn-ghost" style="flex:1" onclick="utToast('Continue with Google')"><x-frontend.icon n="mail" :size="18" /> Google</button>
            </div>
            <div class="ut-row" style="gap:14px;margin:22px 0"><hr class="divider" style="flex:1"><span class="muted" style="font-size:12.5px;font-family:var(--font-head);font-weight:600">OR</span><hr class="divider" style="flex:1"></div>

            <form class="ut-col" style="gap:16px" action="{{ route('frontend.account.dashboard') }}" method="GET">
                <div class="field"><label>Email address</label><input class="ut-input" type="email" value="alex@email.com" placeholder="you@email.com"></div>
                <div class="field">
                    <div class="ut-row" style="justify-content:space-between"><label>Password</label><a href="{{ route('frontend.password.request') }}" style="color:var(--blue);font-family:var(--font-head);font-weight:600;font-size:13px">Forgot?</a></div>
                    <div style="position:relative"><input class="ut-input" type="password" value="password" style="padding-right:64px"><button type="button" data-toggle-pw style="position:absolute;right:12px;top:11px;border:0;background:none;color:var(--text-2);font-family:var(--font-head);font-weight:600;font-size:12.5px">Show</button></div>
                </div>
                <label class="ut-row" style="gap:9px;font-size:14px"><input type="checkbox" checked style="accent-color:var(--blue);width:16px;height:16px"> Keep me signed in</label>
                <button class="ut-btn ut-btn-ink ut-btn-block ut-btn-lg" type="submit">Sign in</button>
            </form>
            <p class="muted" style="text-align:center;margin-top:26px;font-size:14px">New to T-Shirt Shop? <a href="{{ route('frontend.register') }}" style="color:var(--blue);font-weight:600;font-family:var(--font-head)">Create account</a></p>
        </div>
    </div>
</div>
@endsection


