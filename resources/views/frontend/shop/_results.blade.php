{{-- Product grid + active-filter chips + pagination. Rendered both on the
     initial page load (via @include from index.blade.php) and standalone for
     AJAX filter/pagination requests (ShopController swaps #shopResults'
     innerHTML with this markup) — self-contained, only variables the
     controller passes to the view. "Clear all" and pagination links carry
     .js-shop-nav / live inside .ut-pager so index.blade.php's delegated
     click handler can AJAX-load them instead of navigating. --}}
@php
    $f = $filters;
    $activeChips = [];
    if ($activeCat !== 'All') $activeChips[] = $activeCat;
    if ($activeSub !== 'All' && $activeSub !== $activeCat) $activeChips[] = $activeSub;
    if ($activeBrand !== 'All') $activeChips[] = $activeBrand;
    foreach ($activeSizes as $s) $activeChips[] = $s;
    foreach ($activeColors as $c) $activeChips[] = ($colors[$c]['name'] ?? $c);
    if ($f['sale']) $activeChips[] = __('Sale only');
    if ($f['new']) $activeChips[] = __('New arrivals');
    if ($f['best']) $activeChips[] = __('Best sellers');
    if ($f['max_price'] !== null && (int) $f['max_price'] < $maxPrice) $activeChips[] = __('Under').' $'.(int) $f['max_price'];
    if (filled($f['q'])) $activeChips[] = '“'.$f['q'].'”';
@endphp
@if(count($activeChips))
    <div class="ut-active-filters" aria-live="polite">
        @foreach($activeChips as $chip)<span class="ut-active-filter">{{ $chip }}</span>@endforeach
        <a href="{{ route('frontend.shop.index') }}" class="js-shop-nav" style="text-decoration:none"><button type="button">{{ __('Clear all') }}</button></a>
    </div>
@endif
<div class="ut-row" style="justify-content:space-between;margin-bottom:20px;gap:12px;flex-wrap:wrap">
    <span class="muted" style="font-size:14px">{{ __('Showing') }} <b style="color:var(--ink)">{{ $products->count() }}</b> {{ __('of') }} {{ $products->total() }}</span>
    <div class="ut-row" style="gap:8px">
        <span class="muted" style="font-size:13px">{{ __('Sort') }}</span>
        <select name="sort" form="shopFilter" class="ut-input" style="padding:9px 36px 9px 14px;border-radius:var(--r-pill);font-family:var(--font-head);font-weight:500;font-size:13px;width:auto" onchange="submitFilter()">
            <option value="featured" @selected($f['sort'] === 'featured')>{{ __('Featured') }}</option>
            <option value="newest" @selected($f['sort'] === 'newest')>{{ __('Newest') }}</option>
            <option value="low" @selected($f['sort'] === 'low')>{{ __('Price: Low to High') }}</option>
            <option value="high" @selected($f['sort'] === 'high')>{{ __('Price: High to Low') }}</option>
            <option value="rated" @selected($f['sort'] === 'rated')>{{ __('Top rated') }}</option>
        </select>
    </div>
</div>

@if($products->isEmpty())
    <div class="ut-card" style="text-align:center;padding:80px 20px">
        <div style="width:60px;height:60px;border-radius:18px;background:var(--bg);display:grid;place-items:center;margin:0 auto 16px;color:var(--text-2)"><x-frontend.icon n="search" :size="26" /></div>
        <h3>{{ __('No products match') }}</h3><p class="muted" style="margin-top:6px">{{ __('Try clearing a filter or two.') }}</p>
        <a href="{{ route('frontend.shop.index') }}" class="ut-btn ut-btn-ink ut-btn-sm js-shop-nav" style="margin-top:16px;text-decoration:none">{{ __('Clear all') }}</a>
    </div>
@else
    <div class="ut-results-grid" id="productGrid">
        @foreach($products as $p)
            <div class="product-cell">
                <x-frontend.product-card :product="$p" />
            </div>
        @endforeach
    </div>

    @if($products->hasPages())
        <nav class="ut-pager" aria-label="{{ __('Pagination Navigation') }}">
            @if($products->onFirstPage())
                <span class="is-disabled" aria-hidden="true"><x-frontend.icon n="arrowL" :size="16" /></span>
            @else
                <a href="{{ $products->previousPageUrl() }}" rel="prev" aria-label="{{ __('Previous') }}"><x-frontend.icon n="arrowL" :size="16" /></a>
            @endif

            @foreach($products->getUrlRange(max(1, $products->currentPage() - 2), min($products->lastPage(), $products->currentPage() + 2)) as $page => $url)
                @if($page === $products->currentPage())
                    <span class="is-active" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if($products->hasMorePages())
                <a href="{{ $products->nextPageUrl() }}" rel="next" aria-label="{{ __('Next') }}"><x-frontend.icon n="arrowR" :size="16" /></a>
            @else
                <span class="is-disabled" aria-hidden="true"><x-frontend.icon n="arrowR" :size="16" /></span>
            @endif
        </nav>
    @endif
@endif
