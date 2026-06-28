{{-- reusable product rail section --}}
<section class="ut-wrap" style="margin-top:96px">
    <div class="ut-sec-head" data-reveal>
        <div>
            <span class="ut-eyebrow">{{ $eyebrow }}</span>
            <h2 style="margin-top:14px">{{ $title }}</h2>
        </div>
        <a href="{{ route('frontend.shop.index') }}" class="ut-link">Shop all <x-frontend.icon n="arrowR" :size="16" /></a>
    </div>
    <div class="ut-rail">
        @foreach($items as $p)
            <x-frontend.product-card :product="$p" />
        @endforeach
    </div>
</section>


