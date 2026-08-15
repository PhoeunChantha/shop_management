@extends('frontend.layouts.frontend')
@section('title', __('Shop All Products').' — T-Shirt Shop')

@push('head')
<style>
    .ut-listing-grid { display:grid; grid-template-columns:248px 1fr; gap:36px; align-items:start; }
    @media (max-width:1024px){ .ut-listing-grid{ grid-template-columns:1fr; } .ut-filters-side{ display:none; } .ut-mobile-filter{ display:inline-flex !important; } }
    .ut-pager { display:flex; align-items:center; justify-content:center; gap:6px; margin-top:36px; flex-wrap:wrap; }
    .ut-pager a, .ut-pager span { min-width:40px; height:40px; padding:0 12px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; border:1px solid var(--border); font-family:var(--font-head); font-weight:600; font-size:14px; background:#fff; color:var(--ink); text-decoration:none; }
    .ut-pager a:hover { border-color:var(--ink); }
    .ut-pager .is-active { background:var(--ink); color:#fff; border-color:var(--ink); }
    .ut-pager .is-disabled { opacity:.4; pointer-events:none; }
    /* Reusable filter-section heading (replaces the repeated inline style). */
    .ut-filter-heading { font-family:var(--font-head); font-weight:700; font-size:13px; text-transform:uppercase; letter-spacing:.06em; margin-bottom:12px; }
</style>
@endpush

@php
    // Server is the source of truth for filtering. Every control lives inside the
    // GET form below; changing one submits the form and reloads a filtered,
    // paginated page. Active states are rendered from $filters (no client filter).
    $f = $filters;
    $activeCat = $f['category'] ?: 'All';
    $activeSub = $f['subcategory'] ?: 'All';
    $activeBrand = $f['brand'] ?: 'All';
    $activeSizes = $f['sizes'] ?? [];
    $activeColors = $f['colors'] ?? [];
    $priceValue = $f['max_price'] !== null ? (int) $f['max_price'] : $maxPrice;
@endphp

@section('content')
<div class="anim-up">
    {{-- page head --}}
    <div style="background:#fff;border-bottom:1px solid var(--border)">
        <div class="ut-wrap" style="padding:30px 24px 24px">
            <x-frontend.breadcrumb :items="[[__('Home'), route('frontend.home')], [__('Shop all products'), null]]" />
            <div class="ut-row" style="justify-content:space-between;flex-wrap:wrap;gap:16px;align-items:flex-end;margin-top:8px">
                <div><h1 style="font-size:clamp(30px,4vw,46px)">{{ __('All Products') }}</h1><p class="muted" style="margin-top:6px">{{ $catalogTotal }} {{ __('products · curated shop catalog') }}</p></div>
                <div style="position:relative;min-width:280px">
                    <span style="position:absolute;left:14px;top:13px;color:var(--text-2)"><x-frontend.icon n="search" :size="18" /></span>
                    <input class="ut-input" id="shopSearch" name="q" form="shopFilter" value="{{ $f['q'] }}" placeholder="{{ __('Search products…') }}" autocomplete="off" style="padding-left:42px;border-radius:var(--r-pill)">
                </div>
            </div>
        </div>
    </div>

    <div class="ut-wrap" style="padding-top:28px">
        {{-- All controls post to this single GET form; JS helpers set hidden values then submit. --}}
        <form id="shopFilter" method="GET" action="{{ route('frontend.shop.index') }}">
            <input type="hidden" name="category" id="fCategory" value="{{ $activeCat === 'All' ? '' : $activeCat }}">
            <input type="hidden" name="subcategory" id="fSubcategory" value="{{ $activeSub === 'All' ? '' : $activeSub }}">
            <input type="hidden" name="brand" id="fBrand" value="{{ $activeBrand === 'All' ? '' : $activeBrand }}">
            <input type="hidden" name="sizes" id="fSizes" value="{{ implode(',', $activeSizes) }}">
            <input type="hidden" name="colors" id="fColors" value="{{ implode(',', $activeColors) }}">
        </form>

        <div class="ut-listing-grid">
            {{-- FILTERS --}}
            <aside class="ut-filters-side" style="position:sticky;top:160px">
                <div class="ut-col" style="gap:26px">
                    <div>
                        <div class="ut-filter-heading">{{ __('Category') }}</div>
                        <div class="ut-col" style="gap:4px" id="catFilter">
                            <button type="button" class="cat-btn {{ $activeCat === 'All' ? 'is-active' : '' }}" data-cat="All" onclick="setCat('')" style="border:0;text-align:left;padding:8px 12px;border-radius:10px;font-family:var(--font-head);font-weight:600;font-size:14px;display:flex;justify-content:space-between">{{ __('All') }} <span class="muted" style="font-weight:500">{{ $catalogTotal }}</span></button>
                            @foreach($categories as $category => $details)
                                <div class="ut-filter-group {{ $activeCat === $category ? '' : 'is-collapsed' }}">
                                    <button type="button" class="cat-btn ut-parent-cat {{ $activeCat === $category && $activeSub === 'All' ? 'is-active' : '' }}" data-cat="{{ $category }}" onclick="setCat(@js($category))" aria-expanded="{{ $activeCat === $category ? 'true' : 'false' }}"><span>{{ $category }}</span><span class="muted">{{ $details['count'] }} <x-frontend.icon n="chevD" :size="14" /></span></button>
                                    <div class="ut-subcategory-list">
                                        @foreach($details['subcategories'] as $subcategory => $count)
                                            <button type="button" class="subcat-btn {{ $activeCat === $category && $activeSub === $subcategory ? 'is-active' : '' }}" data-cat="{{ $category }}" data-subcat="{{ $subcategory }}" onclick="setSubcat(@js($category), @js($subcategory))"><span>{{ $subcategory }}</span><span>{{ $count }}</span></button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <hr class="divider">
                    <div>
                        <div class="ut-filter-heading">{{ __('Availability') }}</div>
                        <label class="ut-filter-toggle"><span>{{ __('Sale only') }}</span><input type="checkbox" name="sale" value="1" form="shopFilter" @checked($f['sale']) onchange="submitFilter()"><i></i></label>
                        <label class="ut-filter-toggle"><span>{{ __('New arrivals') }}</span><input type="checkbox" name="new" value="1" form="shopFilter" @checked($f['new']) onchange="submitFilter()"><i></i></label>
                        <label class="ut-filter-toggle"><span>{{ __('Best sellers') }}</span><input type="checkbox" name="best" value="1" form="shopFilter" @checked($f['best']) onchange="submitFilter()"><i></i></label>
                    </div>
                    <hr class="divider">
                    <div>
                        <div class="ut-filter-heading">{{ __('Brand') }}</div>
                        <div class="ut-col" style="gap:4px" id="brandFilter">
                            <button type="button" class="brand-btn {{ $activeBrand === 'All' ? 'is-active' : '' }}" data-brand="All" onclick="setBrand('')"><span>{{ __('All brands') }}</span><span>{{ $catalogTotal }}</span></button>
                            @foreach($brands as $brand => $count)
                                <button type="button" class="brand-btn {{ $activeBrand === $brand ? 'is-active' : '' }}" data-brand="{{ $brand }}" onclick="setBrand(@js($brand))"><span>{{ $brand }}</span><span>{{ $count }}</span></button>
                            @endforeach
                        </div>
                    </div>
                    <hr class="divider">
                    <div>
                        <div class="ut-filter-heading">{{ __('Size') }}</div>
                        <div style="display:flex;flex-wrap:wrap;gap:8px">
                            @foreach($sizes as $s)
                                <button type="button" class="ut-chip size-btn {{ in_array($s, $activeSizes, true) ? 'is-active' : '' }}" data-size="{{ $s }}" style="width:50px;justify-content:center;padding:9px 0" onclick="toggleSize(@js($s))">{{ $s }}</button>
                            @endforeach
                        </div>
                    </div>
                    <hr class="divider">
                    <div>
                        <div class="ut-filter-heading">{{ __('Color') }}</div>
                        <div style="display:flex;flex-wrap:wrap;gap:12px">
                            @foreach($colors as $k => $c)
                                <button type="button" class="color-btn" data-color="{{ $k }}" style="border:0;background:none;padding:0" title="{{ $c['name'] }}" onclick="toggleColor(@js((string) $k))">
                                    <span class="swatch {{ in_array((string) $k, $activeColors, true) ? 'is-active' : '' }}" style="background:{{ $c['hex'] }};width:28px;height:28px"></span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <hr class="divider">
                    <div>
                        <div class="ut-filter-heading">{{ __('Max price') }} — <span id="priceVal" style="color:var(--blue)">${{ $priceValue }}</span></div>
                        <input type="range" name="max_price" form="shopFilter" min="{{ $minPrice }}" max="{{ $maxPrice }}" value="{{ $priceValue }}" style="width:100%;accent-color:var(--blue)" oninput="document.getElementById('priceVal').textContent='$'+this.value" onchange="submitFilter()">
                        <div class="ut-row muted" style="justify-content:space-between;font-size:12px;margin-top:4px"><span>${{ $minPrice }}</span><span>${{ $maxPrice }}</span></div>
                    </div>
                </div>
            </aside>

            {{-- RESULTS --}}
            <div>
                @php
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
                        <a href="{{ route('frontend.shop.index') }}" style="text-decoration:none"><button type="button">{{ __('Clear all') }}</button></a>
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
                        <a href="{{ route('frontend.shop.index') }}" class="ut-btn ut-btn-ink ut-btn-sm" style="margin-top:16px;text-decoration:none">{{ __('Clear all') }}</a>
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
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
        var form = document.getElementById('shopFilter');
        function submitForm(){
            // Drop empty params so the URL stays clean; unchecked boxes are omitted natively.
            form.querySelectorAll('input[type=hidden], input[name=q], input[name=max_price]').forEach(function(el){
                if(!el.value) el.disabled = true;
            });
            form.requestSubmit ? form.requestSubmit() : form.submit();
        }
        window.submitFilter = submitForm;
        window.setCat = function(cat){
            document.getElementById('fCategory').value = cat || '';
            document.getElementById('fSubcategory').value = '';
            submitForm();
        };
        window.setSubcat = function(cat, sub){
            document.getElementById('fCategory').value = cat || '';
            document.getElementById('fSubcategory').value = sub || '';
            submitForm();
        };
        window.setBrand = function(brand){
            document.getElementById('fBrand').value = brand || '';
            submitForm();
        };
        function currentList(id){
            var v = document.getElementById(id).value;
            return v ? v.split(',').filter(Boolean) : [];
        }
        window.toggleSize = function(size){
            var list = currentList('fSizes');
            var i = list.indexOf(size);
            if(i > -1) list.splice(i, 1); else list.push(size);
            document.getElementById('fSizes').value = list.join(',');
            submitForm();
        };
        window.toggleColor = function(color){
            var list = currentList('fColors');
            var i = list.indexOf(color);
            if(i > -1) list.splice(i, 1); else list.push(color);
            document.getElementById('fColors').value = list.join(',');
            submitForm();
        };
        // Debounced live search — the input lives outside the form (form="shopFilter"),
        // so submit by id rather than relying on closest('form').
        var search = document.getElementById('shopSearch');
        if(search){
            var timer;
            search.addEventListener('input', function(){
                clearTimeout(timer);
                timer = setTimeout(submitForm, 450);
            });
        }
    })();
</script>
@endpush
