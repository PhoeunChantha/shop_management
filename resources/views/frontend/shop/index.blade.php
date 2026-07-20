@extends('frontend.layouts.frontend')
@section('title', 'Shop All Products — T-Shirt Shop')

@push('head')
<style>
    .ut-listing-grid { display:grid; grid-template-columns:248px 1fr; gap:36px; align-items:start; }
    @media (max-width:1024px){ .ut-listing-grid{ grid-template-columns:1fr; } .ut-filters-side{ display:none; } .ut-mobile-filter{ display:inline-flex !important; } }
</style>
@endpush

@section('content')
<div class="anim-up">
    {{-- page head --}}
    <div style="background:#fff;border-bottom:1px solid var(--border)">
        <div class="ut-wrap" style="padding:30px 24px 24px">
            <x-frontend.breadcrumb :items="[['Home', route('frontend.home')], ['Shop all products', null]]" />
            <div class="ut-row" style="justify-content:space-between;flex-wrap:wrap;gap:16px;align-items:flex-end;margin-top:8px">
                <div><h1 style="font-size:clamp(30px,4vw,46px)">All Products</h1><p class="muted" style="margin-top:6px">{{ count($products) }} products · curated shop catalog</p></div>
                <div style="position:relative;min-width:280px">
                    <span style="position:absolute;left:14px;top:13px;color:var(--text-2)"><x-frontend.icon n="search" :size="18" /></span>
                    <input class="ut-input" id="shopSearch" placeholder="Search products…" style="padding-left:42px;border-radius:var(--r-pill)" oninput="filterProducts()">
                </div>
            </div>
        </div>
    </div>

    <div class="ut-wrap" style="padding-top:28px">
        <div class="ut-listing-grid">
            {{-- FILTERS --}}
            <aside class="ut-filters-side" style="position:sticky;top:160px">
                <div class="ut-col" style="gap:26px">
                    <div>
                        <div style="font-family:var(--font-head);font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">Category</div>
                        <div class="ut-col" style="gap:4px" id="catFilter">
                            <button type="button" class="cat-btn is-active" data-cat="All" onclick="setCat(this)" style="border:0;text-align:left;padding:8px 12px;border-radius:10px;font-family:var(--font-head);font-weight:600;font-size:14px;display:flex;justify-content:space-between">All <span class="muted" style="font-weight:500">{{ count($products) }}</span></button>
                            @foreach($categories as $category => $details)
                                <div class="ut-filter-group">
                                    <button type="button" class="cat-btn ut-parent-cat" data-cat="{{ $category }}" onclick="toggleCategory(this)" aria-expanded="true"><span>{{ $category }}</span><span class="muted">{{ $details['count'] }} <x-frontend.icon n="chevD" :size="14" /></span></button>
                                    <div class="ut-subcategory-list">
                                        @foreach($details['subcategories'] as $subcategory => $count)
                                            <button type="button" class="subcat-btn" data-cat="{{ $category }}" data-subcat="{{ $subcategory }}" onclick="setSubcat(this)"><span>{{ $subcategory }}</span><span>{{ $count }}</span></button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <hr class="divider">
                    <div>
                        <div style="font-family:var(--font-head);font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">Availability</div>
                        <label class="ut-filter-toggle"><span>Sale only</span><input id="saleOnly" type="checkbox" onchange="window.UT_SALE=this.checked; filterProducts()"><i></i></label>
                    </div>
                    <hr class="divider">
                    <div>
                        <div style="font-family:var(--font-head);font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">Brand</div>
                        <div class="ut-col" style="gap:4px" id="brandFilter">
                            <button type="button" class="brand-btn is-active" data-brand="All" onclick="setBrand(this)"><span>All brands</span><span>{{ count($products) }}</span></button>
                            @foreach($brands as $brand => $count)
                                <button type="button" class="brand-btn" data-brand="{{ $brand }}" onclick="setBrand(this)"><span>{{ $brand }}</span><span>{{ $count }}</span></button>
                            @endforeach
                        </div>
                    </div>
                    <hr class="divider">
                    <div>
                        <div style="font-family:var(--font-head);font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">Size</div>
                        <div style="display:flex;flex-wrap:wrap;gap:8px">
                            @foreach($sizes as $s)
                                <button type="button" class="ut-chip size-btn" data-size="{{ $s }}" style="width:50px;justify-content:center;padding:9px 0" onclick="setSize(this)">{{ $s }}</button>
                            @endforeach
                        </div>
                    </div>
                    <hr class="divider">
                    <div>
                        <div style="font-family:var(--font-head);font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">Color</div>
                        <div style="display:flex;flex-wrap:wrap;gap:12px">
                            @foreach($colors as $k => $c)
                                <button type="button" class="color-btn" data-color="{{ $k }}" style="border:0;background:none;padding:0" title="{{ $c['name'] }}" onclick="setColor(this)">
                                    <span class="swatch" style="background:{{ $c['hex'] }};width:28px;height:28px"></span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <hr class="divider">
                    <div>
                        <div style="font-family:var(--font-head);font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">Max price — <span id="priceVal" style="color:var(--blue)">${{ $maxPrice }}</span></div>
                        <input type="range" min="{{ $minPrice }}" max="{{ $maxPrice }}" value="{{ $maxPrice }}" style="width:100%;accent-color:var(--blue)" oninput="document.getElementById('priceVal').textContent='$'+this.value; window.UT_MAXPRICE=+this.value; filterProducts()">
                        <div class="ut-row muted" style="justify-content:space-between;font-size:12px;margin-top:4px"><span>${{ $minPrice }}</span><span>${{ $maxPrice }}</span></div>
                    </div>
                </div>
            </aside>

            {{-- RESULTS --}}
            <div>
                <div id="activeFilters" class="ut-active-filters" aria-live="polite"></div>
                <div class="ut-row" style="justify-content:space-between;margin-bottom:20px;gap:12px;flex-wrap:wrap">
                    <span class="muted" style="font-size:14px">Showing <b id="shownCount" style="color:var(--ink)">{{ count($products) }}</b> of {{ count($products) }}</span>
                    <div class="ut-row" style="gap:8px">
                        <span class="muted" style="font-size:13px">Sort</span>
                        <select class="ut-input" style="padding:9px 36px 9px 14px;border-radius:var(--r-pill);font-family:var(--font-head);font-weight:500;font-size:13px;width:auto" onchange="sortProducts(this.value)">
                            <option value="featured">Featured</option>
                            <option value="newest">Newest</option>
                            <option value="low">Price: Low to High</option>
                            <option value="high">Price: High to Low</option>
                            <option value="rated">Top rated</option>
                        </select>
                    </div>
                </div>

                <div class="ut-results-grid" id="productGrid">
                    @foreach($products as $p)
                        <div class="product-cell" data-cat="{{ $p['cat'] }}" data-subcat="{{ $p['subcat'] }}" data-brand="{{ $p['brand'] }}" data-sale="{{ $p['tag'] === 'sale' ? 1 : 0 }}" data-price="{{ $p['price'] }}" data-name="{{ strtolower($p['name']) }}" data-search="{{ strtolower($p['name'].' '.$p['cat'].' '.$p['subcat'].' '.$p['brand']) }}" data-sizes="{{ implode('|', $p['sizes'] ?? []) }}" data-colors="{{ implode('|', $p['colors'] ?? []) }}"
                             data-rating="{{ $p['rating'] }}" data-new="{{ $p['tag'] === 'new' ? 1 : 0 }}" data-order="{{ $loop->index }}">
                            <x-frontend.product-card :product="$p" />
                        </div>
                    @endforeach
                </div>

                <div id="noResults" class="ut-card" style="display:none;text-align:center;padding:80px 20px">
                    <div style="width:60px;height:60px;border-radius:18px;background:var(--bg);display:grid;place-items:center;margin:0 auto 16px;color:var(--text-2)"><x-frontend.icon n="search" :size="26" /></div>
                    <h3>No products match</h3><p class="muted" style="margin-top:6px">Try clearing a filter or two.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.UT_CAT = 'All'; window.UT_SUBCAT = 'All'; window.UT_BRAND = 'All'; window.UT_SALE = false; window.UT_MAXPRICE = {{ $maxPrice }}; window.UT_PRICE_CEILING = {{ $maxPrice }}; window.UT_SIZES = []; window.UT_COLORS = [];
    function filterKey(value){
        return String(value || '').trim().toLowerCase().replace(/&/g, 'and').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }
    function filterMatches(actual, expected){
        return expected === 'All' || actual === expected || filterKey(actual) === filterKey(expected);
    }
    function filterOptionMatches(actual, expected){
        return expected === 'All' ? actual === 'All' : filterMatches(actual, expected);
    }
    function setCat(btn){
        document.querySelectorAll('.cat-btn, .subcat-btn').forEach(b=>b.classList.remove('is-active'));
        btn.classList.add('is-active'); window.UT_CAT = btn.getAttribute('data-cat'); window.UT_SUBCAT = 'All'; filterProducts();
    }
    function toggleCategory(btn){
        setCat(btn);
        var group = btn.closest('.ut-filter-group');
        var isCollapsed = group.classList.toggle('is-collapsed');
        btn.setAttribute('aria-expanded', String(!isCollapsed));
    }
    function setSubcat(btn){
        document.querySelectorAll('.cat-btn, .subcat-btn').forEach(b=>b.classList.remove('is-active'));
        btn.classList.add('is-active'); window.UT_CAT = btn.getAttribute('data-cat'); window.UT_SUBCAT = btn.getAttribute('data-subcat'); filterProducts();
    }
    function setBrand(btn){
        document.querySelectorAll('.brand-btn').forEach(b=>b.classList.remove('is-active'));
        btn.classList.add('is-active'); window.UT_BRAND = btn.getAttribute('data-brand'); filterProducts();
    }
    function setSize(btn){
        btn.classList.toggle('is-active');
        window.UT_SIZES = [].slice.call(document.querySelectorAll('.size-btn.is-active')).map(function(button){ return button.dataset.size; });
        filterProducts();
    }
    function setColor(btn){
        btn.firstElementChild.classList.toggle('is-active');
        window.UT_COLORS = [].slice.call(document.querySelectorAll('.color-btn .swatch.is-active')).map(function(swatch){ return swatch.closest('.color-btn').dataset.color; });
        filterProducts();
    }
    function clearFilters(){
        window.UT_CAT = 'All'; window.UT_SUBCAT = 'All'; window.UT_BRAND = 'All'; window.UT_SALE = false; window.UT_MAXPRICE = window.UT_PRICE_CEILING; window.UT_SIZES = []; window.UT_COLORS = [];
        document.querySelector('.cat-btn[data-cat="All"]').classList.add('is-active');
        document.querySelectorAll('.cat-btn:not([data-cat="All"]), .subcat-btn, .brand-btn, .size-btn').forEach(b => b.classList.remove('is-active'));
        document.querySelectorAll('.color-btn .swatch').forEach(swatch => swatch.classList.remove('is-active'));
        document.querySelector('.brand-btn[data-brand="All"]').classList.add('is-active');
        document.getElementById('saleOnly').checked = false;
        document.querySelector('input[type="range"]').value = window.UT_PRICE_CEILING;
        document.getElementById('priceVal').textContent = '$' + window.UT_PRICE_CEILING;
        filterProducts();
    }
    function renderActiveFilters(){
        var container = document.getElementById('activeFilters');
        var filters = [];
        if(window.UT_CAT !== 'All') filters.push(window.UT_CAT);
        if(window.UT_SUBCAT !== 'All' && filterKey(window.UT_SUBCAT) !== filterKey(window.UT_CAT)) filters.push(window.UT_SUBCAT);
        if(window.UT_BRAND !== 'All') filters.push(window.UT_BRAND);
        window.UT_SIZES.forEach(function(size){ filters.push(size); });
        window.UT_COLORS.forEach(function(color){ filters.push(color); });
        if(window.UT_SALE) filters.push('Sale only');
        if(window.UT_MAXPRICE < window.UT_PRICE_CEILING) filters.push('Under $' + window.UT_MAXPRICE);
        container.innerHTML = '';
        if(!filters.length) return;
        filters.forEach(function(label){ var chip=document.createElement('span'); chip.className='ut-active-filter'; chip.textContent=label; container.appendChild(chip); });
        var clear=document.createElement('button'); clear.type='button'; clear.textContent='Clear all'; clear.onclick=clearFilters; container.appendChild(clear);
    }
    function syncFilterUrl(){
        var params = new URLSearchParams();
        if(window.UT_CAT !== 'All') params.set('category', window.UT_CAT);
        if(window.UT_SUBCAT !== 'All') params.set('subcategory', window.UT_SUBCAT);
        if(window.UT_BRAND !== 'All') params.set('brand', window.UT_BRAND);
        if(window.UT_SIZES.length) params.set('sizes', window.UT_SIZES.join(','));
        if(window.UT_COLORS.length) params.set('colors', window.UT_COLORS.join(','));
        if(window.UT_SALE) params.set('sale', '1');
        if(window.UT_MAXPRICE < window.UT_PRICE_CEILING) params.set('max_price', window.UT_MAXPRICE);
        history.replaceState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : ''));
    }
    function filterProducts(){
        var q = (document.getElementById('shopSearch').value||'').toLowerCase();
        var shown = 0;
        document.querySelectorAll('.product-cell').forEach(function(cell){
            var ok = filterMatches(cell.dataset.cat, window.UT_CAT)
                  && filterMatches(cell.dataset.subcat, window.UT_SUBCAT)
                  && filterMatches(cell.dataset.brand, window.UT_BRAND)
                  && (!window.UT_SIZES.length || window.UT_SIZES.some(function(size){ return (cell.dataset.sizes || '').split('|').indexOf(size) > -1; }))
                  && (!window.UT_COLORS.length || window.UT_COLORS.some(function(color){ return (cell.dataset.colors || '').split('|').indexOf(color) > -1; }))
                  && (!window.UT_SALE || cell.dataset.sale === '1')
                  && (+cell.dataset.price <= window.UT_MAXPRICE)
                  && (!q || (cell.dataset.search || cell.dataset.name).indexOf(q) > -1);
            cell.style.display = ok ? '' : 'none';
            if(ok){
                cell.classList.remove('ut-product-reveal');
                if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches){
                    void cell.offsetWidth;
                    cell.style.setProperty('--reveal-delay', Math.min(shown * 45, 240) + 'ms');
                    cell.classList.add('ut-product-reveal');
                }
                shown++;
            }
        });
        document.getElementById('shownCount').textContent = shown;
        document.getElementById('noResults').style.display = shown===0 ? '' : 'none';
        document.getElementById('productGrid').style.display = shown===0 ? 'none' : '';
        renderActiveFilters(); syncFilterUrl();
    }
    function sortProducts(mode){
        var grid = document.getElementById('productGrid');
        var cells = [].slice.call(grid.children);
        cells.sort(function(a,b){
            if(mode==='low') return a.dataset.price - b.dataset.price;
            if(mode==='high') return b.dataset.price - a.dataset.price;
            if(mode==='rated') return b.dataset.rating - a.dataset.rating;
            if(mode==='newest') return b.dataset.new - a.dataset.new;
            return a.dataset.order - b.dataset.order;
        });
        cells.forEach(function(c){ grid.appendChild(c); });
    }
    (function restoreFilterState(){
        var params = new URLSearchParams(window.location.search);
        window.UT_CAT = params.get('category') || 'All';
        window.UT_SUBCAT = params.get('subcategory') || 'All';
        window.UT_BRAND = params.get('brand') || 'All';
        window.UT_SALE = params.get('sale') === '1';
        window.UT_SIZES = (params.get('sizes') || '').split(',').filter(Boolean);
        window.UT_COLORS = (params.get('colors') || '').split(',').filter(Boolean);
        window.UT_MAXPRICE = +(params.get('max_price') || window.UT_PRICE_CEILING);
        document.querySelectorAll('.cat-btn, .subcat-btn, .brand-btn').forEach(function(button){
            var hasCat = Object.prototype.hasOwnProperty.call(button.dataset, 'cat');
            var hasSubcat = Object.prototype.hasOwnProperty.call(button.dataset, 'subcat');
            var hasBrand = Object.prototype.hasOwnProperty.call(button.dataset, 'brand');
            var selected = (hasCat && filterOptionMatches(button.dataset.cat, window.UT_CAT) && !hasSubcat && window.UT_SUBCAT === 'All')
                || (hasSubcat && window.UT_SUBCAT !== 'All' && filterMatches(button.dataset.subcat, window.UT_SUBCAT) && filterMatches(button.dataset.cat, window.UT_CAT))
                || (hasBrand && filterOptionMatches(button.dataset.brand, window.UT_BRAND));
            button.classList.toggle('is-active', selected);
            if(selected && hasCat && !hasSubcat) window.UT_CAT = button.dataset.cat;
            if(selected && hasSubcat){ window.UT_CAT = button.dataset.cat; window.UT_SUBCAT = button.dataset.subcat; }
            if(selected && hasBrand) window.UT_BRAND = button.dataset.brand;
        });
        document.querySelectorAll('.size-btn').forEach(function(button){
            button.classList.toggle('is-active', window.UT_SIZES.indexOf(button.dataset.size) > -1);
        });
        document.querySelectorAll('.color-btn').forEach(function(button){
            button.firstElementChild.classList.toggle('is-active', window.UT_COLORS.indexOf(button.dataset.color) > -1);
        });
        document.getElementById('saleOnly').checked = window.UT_SALE;
        document.querySelector('input[type="range"]').value = window.UT_MAXPRICE;
        document.getElementById('priceVal').textContent = '$' + window.UT_MAXPRICE;
        filterProducts();
    })();
</script>
@endpush

