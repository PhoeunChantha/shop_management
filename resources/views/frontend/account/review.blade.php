@extends('frontend.layouts.frontend')
@section('title', 'Write a Review — T-Shirt Shop')

@section('content')
@php($productUrl = $product['url'] ?? route('frontend.shop.show', $product['slug'] ?? \Illuminate\Support\Str::slug($product['name'])))
<div class="ut-wrap anim-up" style="padding-top:28px;max-width:620px">
    <a href="{{ route('frontend.account.orders') }}" class="ut-link" style="margin-bottom:18px;display:inline-flex"><x-frontend.icon n="arrowL" :size="16" /> Back</a>
    <h1 style="font-size:32px;margin-bottom:6px">Write a review</h1>
    <p class="muted" style="margin-bottom:24px">Share your experience to help others shop with confidence.</p>

    <div class="ut-card" id="reviewCard" style="padding:26px">
        <div class="ut-row" style="gap:14px;margin-bottom:24px;padding-bottom:22px;border-bottom:1px solid var(--border)">
            <x-frontend.ph :tint="$product['tint']" :dark="$product['dark']" style="width:64px;height:80px;border-radius:12px" />
            <div><div style="font-family:var(--font-head);font-weight:700;font-size:17px">{{ $product['name'] }}</div><div class="muted" style="font-size:13.5px">{{ $product['cat'] }} · ${{ $product['price'] }}</div></div>
        </div>
        <form class="ut-col" style="gap:22px" onsubmit="return submitReview(event)">
            <input type="hidden" id="ratingVal" value="0">
            <div>
                <label style="font-family:var(--font-head);font-weight:700;font-size:14px;display:block;margin-bottom:10px">Overall rating</label>
                <div class="ut-row" style="gap:6px" id="reviewStars">
                    @for($i = 0; $i < 5; $i++)
                        <button type="button" data-star style="border:0;background:none;padding:2px;color:var(--border)"><x-frontend.icon n="star" :size="34" /></button>
                    @endfor
                </div>
            </div>
            <div>
                <label style="font-family:var(--font-head);font-weight:700;font-size:14px;display:block;margin-bottom:10px">How's the fit?</label>
                <div class="ut-row" style="gap:8px;flex-wrap:wrap">
                    @foreach(['Runs small', 'True to size', 'Runs large'] as $f)
                        <button type="button" class="ut-chip {{ $f === 'True to size' ? 'is-active' : '' }}" onclick="document.querySelectorAll('#reviewCard .ut-chip').forEach(c=>c.classList.remove('is-active')); this.classList.add('is-active')">{{ $f }}</button>
                    @endforeach
                </div>
            </div>
            <div class="field"><label>Review title</label><input class="ut-input" placeholder="Sum it up in a few words"></div>
            <div class="field"><label>Your review</label><textarea class="ut-input" rows="4" placeholder="What did you like or dislike? How's the quality and fit?"></textarea></div>
            <div class="field"><label>Add photos (optional)</label>
                <div class="ut-row" style="gap:10px">
                    @for($i = 0; $i < 3; $i++)
                        <button type="button" class="ph" style="width:72px;height:72px;border-radius:12px;border:1.5px dashed var(--border);align-items:center;justify-content:center;color:var(--text-3)"><x-frontend.icon n="plus" :size="20" /></button>
                    @endfor
                </div>
            </div>
            <button class="ut-btn ut-btn-ink ut-btn-block ut-btn-lg" type="submit">Submit review</button>
        </form>
    </div>
</div>

<style>#reviewStars [data-star].on{ color:var(--accent) !important; }</style>
@endsection

@push('scripts')
<script>
    function submitReview(e){
        e.preventDefault();
        if(+document.getElementById('ratingVal').value === 0){ utToast('Please add a rating'); return false; }
        document.querySelector('.ut-wrap.anim-up').innerHTML =
            '<div style="text-align:center;padding:60px 0"><div style="width:80px;height:80px;border-radius:50%;background:#dcfce7;color:#15803d;display:grid;place-items:center;margin:0 auto 20px"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></div>'+
            '<h1 style="font-size:32px">Thanks for your review!</h1>'+
            '<p class="muted" style="margin:10px 0 24px">You earned <b style="color:var(--ink)">50 thread points</b>. Your review helps other shoppers.</p>'+
            '<div class="ut-row" style="gap:12px;justify-content:center"><a href="{{ $productUrl }}" class="ut-btn ut-btn-ink ut-btn-lg">View product</a><a href="{{ route('frontend.account.orders') }}" class="ut-btn ut-btn-ghost ut-btn-lg">Back to orders</a></div></div>';
        return false;
    }
</script>
@endpush


