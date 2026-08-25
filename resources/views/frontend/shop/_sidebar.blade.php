{{-- Sidebar filter controls. Rendered both on the initial page load (via
     @include from index.blade.php) and standalone for AJAX filter requests
     (ShopController swaps #shopSidebar's innerHTML with this markup), so it
     must stay self-contained and only rely on variables the controller
     passes to the view. --}}
@php $f = $filters; @endphp
<div class="ut-col" style="gap:26px">
    <div>
        <div class="ut-filter-heading">{{ __('Category') }}</div>
        <div class="ut-col" style="gap:4px" id="catFilter">
            <button type="button" class="cat-btn {{ $activeCat === 'All' ? 'is-active' : '' }}" data-cat="All" onclick="setCat('')" style="border:0;text-align:left;padding:8px 12px;border-radius:10px;font-family:var(--font-head);font-weight:600;font-size:14px;display:flex;justify-content:space-between">{{ __('All') }} <span class="muted" style="font-weight:500">{{ $catalogTotal }}</span></button>
            @foreach($categories as $category => $details)
                <div class="ut-filter-group {{ $activeCat === $category ? '' : 'is-collapsed' }}">
                    <div class="cat-btn ut-parent-cat {{ $activeCat === $category && $activeSub === 'All' ? 'is-active' : '' }}" data-cat="{{ $category }}">
                        <button type="button" class="ut-cat-label" onclick="setCat(@js($category))">{{ $category }}</button>
                        @if (count($details['subcategories']))
                            <button type="button" class="muted ut-cat-toggle" aria-expanded="{{ $activeCat === $category ? 'true' : 'false' }}" aria-label="{{ __('Toggle :category sub-categories', ['category' => $category]) }}" onclick="toggleCatGroup(this)">{{ $details['count'] }} <x-frontend.icon n="chevD" :size="14" /></button>
                        @else
                            <span class="muted">{{ $details['count'] }}</span>
                        @endif
                    </div>
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
