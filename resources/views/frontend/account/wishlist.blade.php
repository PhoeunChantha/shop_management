@extends('frontend.account.partials.shell', ['active' => 'wishlist'])
@section('title', __('Wishlist').' — T-Shirt Shop')

@section('account')
<div class="ut-row" style="justify-content:space-between;align-items:flex-end;margin-bottom:18px;gap:12px;flex-wrap:wrap">
    <div><h2 style="font-size:24px">{{ __('Your wishlist') }}</h2><p class="muted" style="font-size:14px;margin-top:4px"><span data-wish-count>0</span> {{ __('saved items') }}</p></div>
    <a href="{{ route('frontend.shop.index') }}" class="ut-btn ut-btn-ghost ut-btn-sm">{{ __('Add more') }}</a>
</div>

{{-- empty state --}}
<div id="wishEmpty" class="ut-card" style="padding:56px;text-align:center;{{ count($products) ? 'display:none' : '' }}">
    <div style="width:64px;height:64px;border-radius:20px;background:var(--bg);display:grid;place-items:center;margin:0 auto 16px;color:var(--text-3)"><x-frontend.icon n="heart" :size="28" /></div>
    <h3>{{ __('No saved items yet') }}</h3><p class="muted" style="margin-top:6px">{{ __('Tap the heart on any product to save it here for later.') }}</p>
    <a href="{{ route('frontend.shop.index') }}" class="ut-btn ut-btn-ink" style="margin-top:18px">{{ __('Browse the collection') }}</a>
</div>

{{-- saved products (server-rendered); a cell is hidden when its heart is toggled off --}}
<div class="ut-results-grid" id="wishGrid" style="{{ count($products) ? '' : 'display:none' }}">
    @foreach($products as $p)
        <div class="wish-cell" data-id="{{ $p['id'] }}"><x-frontend.product-card :product="$p" /></div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
    (function(){
        var grid = document.getElementById('wishGrid');
        if(!grid) return;
        // When a saved item is un-hearted, drop its card and fall back to the
        // empty state once nothing is left. (toggleWish in main.js handles the
        // localStorage + server update.)
        grid.addEventListener('click', function(e){
            var btn = e.target.closest('[data-wish]');
            if(!btn) return;
            setTimeout(function(){
                var wish = []; try { wish = JSON.parse(localStorage.getItem('ut_wish')||'[]'); } catch(err){}
                var any = false;
                grid.querySelectorAll('.wish-cell').forEach(function(c){
                    var on = wish.indexOf(+c.dataset.id) > -1;
                    c.style.display = on ? '' : 'none';
                    if(on) any = true;
                });
                document.getElementById('wishEmpty').style.display = any ? 'none' : '';
                grid.style.display = any ? '' : 'none';
            }, 60);
        });
    })();
</script>
@endpush


