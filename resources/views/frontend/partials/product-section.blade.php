{{-- reusable product rail section --}}
<section class="ut-wrap" style="margin-top:72px">
    <div class="ut-sec-head">
        <div>
            <span class="ut-eyebrow">{{ $eyebrow }}</span>
            <h2 style="margin-top:8px">{{ $title }}</h2>
        </div>
        <a href="{{ route('frontend.shop.index') }}" class="ut-link">Shop all <x-frontend.icon n="arrowR" :size="16" /></a>
    </div>
    <div class="ut-rail">
        @foreach($items as $p)
            <x-frontend.product-card :product="$p" />
        @endforeach
    </div>
</section>


