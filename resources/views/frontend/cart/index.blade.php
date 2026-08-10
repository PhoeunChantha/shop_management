@extends('frontend.layouts.frontend')
@section('title', __('Shopping Bag').' — T-Shirt Shop')

@push('head')
<style>
    .ut-cart-grid { display:grid; grid-template-columns:1fr 380px; gap:36px; align-items:start; }
    @media (max-width:1024px){ .ut-cart-grid{ grid-template-columns:1fr; } .ut-cart-grid .summary{ position:static !important; } }
</style>
@endpush

@section('content')
<div class="ut-wrap anim-up" style="padding-top:32px">

    {{-- empty state (toggled by JS) --}}
    <div id="cartEmpty" style="display:none;padding:80px 24px;text-align:center">
        <div style="width:80px;height:80px;border-radius:24px;background:#fff;box-shadow:var(--sh-2);display:grid;place-items:center;margin:0 auto 22px;color:var(--text-2)"><x-frontend.icon n="bag" :size="34" /></div>
        <h1 style="font-size:34px">{{ __('Your bag is empty') }}</h1>
        <p class="muted" style="margin:10px 0 24px">{{ __("Looks like you haven't added anything yet.") }}</p>
        <a href="{{ route('frontend.shop.index') }}" class="ut-btn ut-btn-ink ut-btn-lg">{{ __('Browse the collection') }}</a>
    </div>

    {{-- cart content --}}
    <div id="cartGrid">
        <h1 style="font-size:clamp(30px,4vw,44px);margin-bottom:6px">{{ __('Shopping bag') }}</h1>
        <p class="muted" style="margin-bottom:28px"><span id="cartItemCount">0</span> {{ __('items in your bag') }}</p>
        <div class="ut-cart-grid">
            <div>
                <div class="ut-card" style="padding:6px 24px"><div id="cartPageLines"></div></div>
                <a href="{{ route('frontend.shop.index') }}" class="ut-link" style="margin-top:18px;display:inline-flex"><x-frontend.icon n="arrowL" :size="16" /> {{ __('Continue shopping') }}</a>

                {{-- cross-sell --}}
                <div style="margin-top:40px">
                    <h3 style="font-size:20px;margin-bottom:16px">{{ __('Pairs well with') }}</h3>
                    <div class="hscroll">
                        @foreach($cross as $p)
                            @php($productUrl = $p['url'] ?? route('frontend.shop.show', $p['slug'] ?? \Illuminate\Support\Str::slug($p['name'])))
                            <div class="ut-card" style="width:200px;padding:12px">
                                <a href="{{ $productUrl }}">
                                    @if (!empty($p['image_url']))
                                        <img src="{{ $p['image_url'] }}" alt="{{ $p['name'] }}" loading="lazy" decoding="async" style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:12px;margin-bottom:10px;display:block">
                                    @else
                                        <x-frontend.ph :tint="$p['tint']" :dark="$p['dark']" style="aspect-ratio:1;border-radius:12px;margin-bottom:10px" />
                                    @endif
                                </a>
                                <div style="font-family:var(--font-head);font-weight:600;font-size:14px">{{ $p['name'] }}</div>
                                <div class="ut-row" style="justify-content:space-between;margin-top:8px">
                                    <span style="font-family:var(--font-head);font-weight:700">{{ money($p['price']) }}</span>
                                    <button type="button" class="ut-btn ut-btn-ghost ut-btn-sm" data-add-to-cart data-no-open
                                            data-id="{{ $p['id'] }}" data-name="{{ $p['name'] }}" data-price="{{ $p['price'] }}" data-tint="{{ $p['tint'] }}" data-image="{{ $p['image_url'] ?? '' }}" data-color="{{ $p['colors'][0] }}" data-size="M">
                                        <x-frontend.icon n="plus" :size="14" /> {{ __('Add') }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- summary --}}
            <div class="ut-card summary" style="padding:26px;position:sticky;top:96px">
                <h3 style="font-size:19px;margin-bottom:18px">{{ __('Order summary') }}</h3>
                <div class="ut-col" style="gap:11px">
                    <div class="ut-row" style="justify-content:space-between;font-size:14.5px"><span class="muted">{{ __('Subtotal') }}</span><span id="sumSubtotal" style="font-weight:600">$0</span></div>
                    <div class="ut-row" id="sumDiscountRow" style="display:none;justify-content:space-between;font-size:14.5px"><span class="muted">{{ __('Discount') }} (<span id="couponCodeLabel"></span>)</span><span id="sumDiscount" style="font-weight:600;color:#15803d">-$0</span></div>
                    <div class="ut-row" style="justify-content:space-between;font-size:14.5px"><span class="muted">{{ __('Shipping') }}</span><span id="sumShipping" style="font-weight:600">{{ __('Free') }}</span></div>
                    <div class="ut-row" style="justify-content:space-between;font-size:14.5px"><span class="muted">{{ __('Estimated tax') }}</span><span id="sumTax" style="font-weight:600">$0</span></div>
                    <hr class="divider" style="margin:6px 0">
                    <div class="ut-row" style="justify-content:space-between"><span style="font-family:var(--font-head);font-weight:700;font-size:17px">{{ __('Total') }}</span><span id="sumTotal" style="font-family:var(--font-head);font-weight:700;font-size:24px">$0</span></div>
                </div>

                {{-- coupon --}}
                <form id="couponForm" style="margin:18px 0">
                    <div class="ut-row" style="gap:8px">
                        <input class="ut-input" placeholder="{{ __('Discount code') }}" autocomplete="off">
                        <button class="ut-btn ut-btn-ink" type="submit" style="flex-shrink:0">{{ __('Apply') }}</button>
                    </div>
                    <div id="couponApplied" style="display:none;align-items:center;gap:6px;margin-top:10px;color:#15803d;font-size:13px;font-weight:600"><x-frontend.icon n="checkC" :size="15" /> <span id="couponAppliedText"></span> <button type="button" onclick="window.__cartRemoveCoupon()" style="border:0;background:none;color:var(--text-3);font-size:12px;cursor:pointer;text-decoration:underline;font-weight:500">{{ __('remove') }}</button></div>
                </form>

                {{-- ship estimate --}}
                <div style="background:var(--bg);border-radius:var(--r-md);padding:14px">
                    <div class="ut-row" style="gap:8px">
                        <span style="color:var(--blue)"><x-frontend.icon n="truck" :size="18" /></span>
                        <span style="font-family:var(--font-head);font-weight:600;font-size:13.5px;flex:1">{{ __('Estimate delivery') }}</span>
                        <input placeholder="{{ __('ZIP') }}" maxlength="5" id="zipEst" style="width:72px;border:1px solid var(--border);border-radius:8px;padding:6px 9px;font-size:13px;font-family:inherit">
                        <button type="button" class="ut-btn ut-btn-ghost ut-btn-sm" onclick="var z=document.getElementById('zipEst').value; if(z){document.getElementById('estResult').style.display='block';document.getElementById('estZip').textContent=z;}">{{ __('Go') }}</button>
                    </div>
                    <p id="estResult" style="display:none;font-size:13px;margin:10px 0 0;color:var(--ink)">{{ __('Arrives') }} <b>Jun 8 – Jun 10</b> {{ __('to') }} <span id="estZip"></span></p>
                </div>

                <a href="{{ route('frontend.checkout.index') }}" class="ut-btn ut-btn-accent ut-btn-block ut-btn-lg" style="margin-top:18px"><x-frontend.icon n="lock" :size="17" /> {{ __('Secure checkout') }}</a>
                <div class="ut-row" style="justify-content:center;gap:14px;margin-top:16px;color:var(--text-3)">
                    @foreach(['card', 'lock', 'shield'] as $ic)
                        <span style="width:42px;height:28px;border-radius:7px;border:1px solid var(--border);display:grid;place-items:center"><x-frontend.icon :n="$ic" :size="16" /></span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


