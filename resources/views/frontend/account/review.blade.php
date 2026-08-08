@extends('frontend.layouts.frontend')
@section('title', __('Write a Review').' — T-Shirt Shop')

@section('content')
@php($productUrl = $product['url'] ?? route('frontend.shop.show', $product['slug'] ?? \Illuminate\Support\Str::slug($product['name'])))
<div class="ut-wrap anim-up" style="padding-top:28px;max-width:620px">
    <a href="{{ route('frontend.account.orders') }}" class="ut-link" style="margin-bottom:18px;display:inline-flex"><x-frontend.icon n="arrowL" :size="16" /> {{ __('Back') }}</a>
    <h1 style="font-size:32px;margin-bottom:6px">{{ __('Write a review') }}</h1>
    <p class="muted" style="margin-bottom:24px">{{ __('Share your experience to help others shop with confidence.') }}</p>

    <div class="ut-card" id="reviewCard" style="padding:26px">
        <div class="ut-row" style="gap:14px;margin-bottom:24px;padding-bottom:22px;border-bottom:1px solid var(--border)">
            <x-frontend.ph :tint="$product['tint']" :dark="$product['dark']" style="width:64px;height:80px;border-radius:12px" />
            <div><div style="font-family:var(--font-head);font-weight:700;font-size:17px">{{ $product['name'] }}</div><div class="muted" style="font-size:13.5px">{{ $product['cat'] }} · {{ money($product['price']) }}</div></div>
        </div>
        <form class="ut-col" style="gap:22px" method="POST" action="{{ route('frontend.account.orders.review.store', ['id' => $order['id'], 'pid' => $product['id']]) }}" onsubmit="return submitReview(event)">
            @csrf
            <input type="hidden" id="ratingVal" name="rating" value="{{ old('rating', 0) }}">
            <div>
                <label style="font-family:var(--font-head);font-weight:700;font-size:14px;display:block;margin-bottom:10px">{{ __('Overall rating') }}</label>
                <div class="ut-row" style="gap:6px" id="reviewStars">
                    @for($i = 0; $i < 5; $i++)
                        <button type="button" data-star style="border:0;background:none;padding:2px;color:var(--border)"><x-frontend.icon n="star" :size="34" /></button>
                    @endfor
                </div>
                @error('rating')<span style="color:var(--accent);font-size:12.5px;margin-top:8px;display:block">{{ $message }}</span>@enderror
            </div>
            <div class="field"><label>{{ __('Review title') }}</label><input class="ut-input" name="title" value="{{ old('title') }}" placeholder="{{ __('Sum it up in a few words') }}">
                @error('title')<span style="color:var(--accent);font-size:12.5px;margin-top:6px;display:block">{{ $message }}</span>@enderror
            </div>
            <div class="field"><label>{{ __('Your review') }}</label><textarea class="ut-input" name="body" rows="4" placeholder="{{ __("What did you like or dislike? How's the quality and fit?") }}" required>{{ old('body') }}</textarea>
                @error('body')<span style="color:var(--accent);font-size:12.5px;margin-top:6px;display:block">{{ $message }}</span>@enderror
            </div>
            <button class="ut-btn ut-btn-ink ut-btn-block ut-btn-lg" type="submit">{{ __('Submit review') }}</button>
        </form>
    </div>
</div>

<style>#reviewStars [data-star].on{ color:var(--accent) !important; }</style>
@endsection

@push('scripts')
<script>
    function submitReview(e){
        if(+document.getElementById('ratingVal').value === 0){ e.preventDefault(); utToast('{{ __('Please add a rating') }}'); return false; }
        return true;
    }
</script>
@endpush


