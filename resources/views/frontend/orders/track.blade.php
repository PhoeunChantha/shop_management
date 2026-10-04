@extends('frontend.layouts.frontend')
@section('title', __('Track your order').' — T-Shirt Shop')

@section('content')
<div class="ut-wrap anim-up" style="padding-top:40px;padding-bottom:40px;max-width:560px">
    <div style="text-align:center;margin-bottom:26px">
        <span class="ut-eyebrow" style="color:var(--blue)">{{ __('Order tracking') }}</span>
        <h1 style="font-size:clamp(28px,3.6vw,40px);margin:10px 0 8px">{{ __('Track your order') }}</h1>
        <p class="muted">{{ __('Enter your order number and the email you used at checkout. You\'ll find both in your confirmation email.') }}</p>
    </div>

    <form class="ut-card" method="POST" action="{{ route('frontend.orders.track.lookup') }}" style="padding:28px">
        @csrf
        <div class="ut-col" style="gap:16px">
            <div class="field">
                <label for="order_number">{{ __('Order number') }}</label>
                <input id="order_number" class="ut-input @error('order_number') is-invalid @enderror" name="order_number" value="{{ old('order_number') }}" placeholder="UT-2026-123456" required autocomplete="off">
                @error('order_number')<span class="ut-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="email">{{ __('Email address') }}</label>
                <input id="email" class="ut-input @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="you@email.com" required autocomplete="email">
                @error('email')<span class="ut-field-error">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="ut-btn ut-btn-ink ut-btn-lg ut-btn-block"><x-frontend.icon n="search" :size="17" /> {{ __('Track order') }}</button>
        </div>
    </form>

    @guest
        <p class="muted" style="text-align:center;font-size:14px;margin-top:18px">
            {{ __('Have an account?') }} <a href="{{ route('frontend.login') }}" style="color:var(--blue);font-weight:600">{{ __('Sign in') }}</a> {{ __('to see all your orders.') }}
        </p>
    @endguest
</div>
@endsection
