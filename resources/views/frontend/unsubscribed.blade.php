@extends('frontend.layouts.frontend')
@section('title', __('Unsubscribed').' — T-Shirt Shop')

@section('content')
<div class="anim-up">
    <div class="ut-wrap" style="max-width:520px;padding:clamp(60px,10vw,120px) 24px;text-align:center">
        <div style="width:64px;height:64px;border-radius:20px;background:var(--bg);display:grid;place-items:center;margin:0 auto 20px;color:var(--text-3)">
            <x-frontend.icon n="check" :size="28" />
        </div>
        <h1 style="font-size:clamp(26px,4vw,34px);margin-bottom:12px">{{ __("You're unsubscribed") }}</h1>
        <p class="muted" style="font-size:15px;line-height:1.6">
            {{ __(':email will no longer receive bulk notification emails from us. Order and account emails (receipts, shipping updates) are unaffected.', ['email' => $email]) }}
        </p>
        <a href="{{ route('frontend.home') }}" class="ut-btn ut-btn-ink" style="margin-top:24px;display:inline-flex">
            {{ __('Back to the shop') }}
        </a>
    </div>
</div>
@endsection
