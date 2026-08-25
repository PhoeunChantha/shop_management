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
    #shopResults.is-loading { opacity:.5; pointer-events:none; transition:opacity .15s ease; }
</style>
@endpush

@php
    // Server is the source of truth for filtering. Every control lives inside the
    // GET form below; changing one AJAX-fetches a filtered, paginated page and
    // swaps #shopSidebar / #shopResults in place (index.blade.php script block)
    // — no full reload. Active states are rendered from $filters either way.
    $f = $filters;
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
            <aside class="ut-filters-side" style="position:sticky;top:160px" id="shopSidebar">
                @include('frontend.shop._sidebar')
            </aside>

            {{-- RESULTS --}}
            <div id="shopResults">
                @include('frontend.shop._results')
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
        var form = document.getElementById('shopFilter');
        var sidebar = document.getElementById('shopSidebar');
        var results = document.getElementById('shopResults');

        // The 5 hidden inputs + the search box live outside the swapped
        // sidebar/results (persistent form / page header), so a navigation
        // that changes filter state via a plain link — pagination,
        // "Clear all", back/forward — has to resync them; otherwise the next
        // setCat/setBrand/etc. would FormData a stale value back in.
        function syncFormFromUrl(url){
            var params = new URL(url, location.origin).searchParams;
            document.getElementById('fCategory').value = params.get('category') || '';
            document.getElementById('fSubcategory').value = params.get('subcategory') || '';
            document.getElementById('fBrand').value = params.get('brand') || '';
            document.getElementById('fSizes').value = params.get('sizes') || '';
            document.getElementById('fColors').value = params.get('colors') || '';
            var search = document.getElementById('shopSearch');
            if(search) search.value = params.get('q') || '';
        }

        // Every filter control AJAX-fetches a filtered page and swaps the
        // sidebar + results markup in place — no full reload. The URL is kept
        // in sync via pushState so back/forward and reload/share still work.
        function loadResults(url, pushState){
            results.classList.add('is-loading');
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    syncFormFromUrl(url);
                    sidebar.innerHTML = data.sidebar;
                    results.innerHTML = data.results;
                    if(pushState !== false) history.pushState({ shopUrl: url }, '', url);
                })
                .catch(function(){ window.location.href = url; })
                .finally(function(){ results.classList.remove('is-loading'); });
        }
        window.addEventListener('popstate', function(e){
            loadResults((e.state && e.state.shopUrl) || location.href, false);
        });

        function submitForm(){
            var params = new URLSearchParams(new FormData(form));
            // Drop empty params so the URL stays clean; unchecked boxes are omitted natively.
            Array.from(params.keys()).forEach(function(key){
                if(!params.get(key)) params.delete(key);
            });
            var qs = params.toString();
            loadResults(form.action + (qs ? '?' + qs : ''));
        }
        window.submitFilter = submitForm;
        window.setCat = function(cat){
            document.getElementById('fCategory').value = cat || '';
            document.getElementById('fSubcategory').value = '';
            submitForm();
        };
        // Expands/collapses a category's sub-category list in place — no
        // navigation, unlike picking the category (setCat) or a sub-category.
        window.toggleCatGroup = function(toggleBtn){
            var group = toggleBtn.closest('.ut-filter-group');
            if(!group) return;
            var collapsed = group.classList.toggle('is-collapsed');
            toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
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
        // Pagination links and "Clear all" (.js-shop-nav) live inside the
        // swapped #shopResults markup — delegate from a stable ancestor so
        // they keep working after every re-render, and AJAX-load instead of
        // navigating. Product card links are untouched (no matching class).
        results.addEventListener('click', function(e){
            var link = e.target.closest('.ut-pager a[href], .js-shop-nav');
            if(!link) return;
            e.preventDefault();
            loadResults(link.href);
        });
    })();
</script>
@endpush
