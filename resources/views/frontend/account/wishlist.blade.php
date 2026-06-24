@extends('frontend.account.partials.shell', ['active' => 'wishlist'])
@section('title', 'Wishlist — T-Shirt Shop')

@section('account')
<div class="ut-row" style="justify-content:space-between;align-items:flex-end;margin-bottom:18px;gap:12px;flex-wrap:wrap">
    <div><h2 style="font-size:24px">Your wishlist</h2><p class="muted" style="font-size:14px;margin-top:4px"><span data-wish-count>0</span> saved items</p></div>
    <a href="{{ route('frontend.shop.index') }}" class="ut-btn ut-btn-ghost ut-btn-sm">Add more</a>
</div>

{{-- empty state (shown when no wishlist) --}}
<div id="wishEmpty" class="ut-card" style="padding:56px;text-align:center">
    <div style="width:64px;height:64px;border-radius:20px;background:var(--bg);display:grid;place-items:center;margin:0 auto 16px;color:var(--text-3)"><x-frontend.icon n="heart" :size="28" /></div>
    <h3>No saved items yet</h3><p class="muted" style="margin-top:6px">Tap the heart on any product to save it here for later.</p>
    <a href="{{ route('frontend.shop.index') }}" class="ut-btn ut-btn-ink" style="margin-top:18px">Browse the collection</a>
</div>

{{-- all products rendered; JS hides non-wished --}}
<div class="ut-results-grid" id="wishGrid" style="display:none">
    @foreach($products as $p)
        <div class="wish-cell" data-id="{{ $p['id'] }}" style="display:none"><x-frontend.product-card :product="$p" /></div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
    (function(){
        var wish = []; try { wish = JSON.parse(localStorage.getItem('ut_wish')||'[]'); } catch(e){}
        var any = false;
        document.querySelectorAll('.wish-cell').forEach(function(c){
            var on = wish.indexOf(+c.dataset.id) > -1;
            c.style.display = on ? '' : 'none';
            if(on) any = true;
        });
        document.getElementById('wishEmpty').style.display = any ? 'none' : '';
        document.getElementById('wishGrid').style.display = any ? '' : 'none';
    })();
</script>
@endpush


