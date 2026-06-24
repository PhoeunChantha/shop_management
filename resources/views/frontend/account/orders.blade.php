@extends('frontend.account.partials.shell', ['active' => 'orders'])
@section('title', 'Order History — T-Shirt Shop')

@section('account')
<div class="ut-row" style="justify-content:space-between;align-items:flex-end;margin-bottom:18px;gap:12px;flex-wrap:wrap">
    <div><h2 style="font-size:24px">Order history</h2><p class="muted" style="font-size:14px;margin-top:4px">{{ count($orders) }} orders placed</p></div>
</div>
<div class="ut-row" style="gap:8px;margin-bottom:18px;flex-wrap:wrap">
    <button class="ut-chip is-active" type="button" onclick="filterOrders(this,'All')">All</button>
    <button class="ut-chip" type="button" onclick="filterOrders(this,'Shipped')">Shipped</button>
    <button class="ut-chip" type="button" onclick="filterOrders(this,'Delivered')">Delivered</button>
</div>
<div class="ut-col" style="gap:14px">
    @foreach($orders as $o)
        <div class="ut-card order-row" data-status="{{ $o['status'] }}" style="padding:20px">
            <div class="ut-row" style="justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px">
                <div><div style="font-family:var(--font-head);font-weight:700;font-size:15px">Order #UT-{{ $o['id'] }}</div><div class="muted" style="font-size:13px">Placed {{ $o['date'] }} · ${{ number_format($o['total'], 2) }}</div></div>
                <span class="ut-tag {{ $o['status'] === 'Delivered' ? 'ut-tag-success' : 'ut-tag-new' }}">{{ $o['status'] }}</span>
            </div>
            <div class="ut-row" style="gap:8px;margin-bottom:16px">
                @foreach($o['items'] as $it)
                    <x-frontend.ph :tint="$products[$it['pid']]['tint'] ?? ''" style="width:48px;height:60px;border-radius:10px" />
                @endforeach
                <span class="muted" style="font-size:13px;align-self:center;margin-left:4px">{{ collect($o['items'])->sum('qty') }} items</span>
            </div>
            <div class="ut-row" style="gap:10px;flex-wrap:wrap">
                <a href="{{ route('frontend.account.orders.show', $o['id']) }}" class="ut-btn ut-btn-ghost ut-btn-sm">View details</a>
                <a href="{{ route('frontend.account.orders.tracking', $o['id']) }}" class="ut-btn ut-btn-ghost ut-btn-sm"><x-frontend.icon n="truck" :size="15" /> Track order</a>
                @if($o['status'] === 'Delivered')
                    <a href="{{ route('frontend.account.orders.review', [$o['id'], $o['items'][0]['pid']]) }}" class="ut-btn ut-btn-ink ut-btn-sm"><x-frontend.icon n="star" :size="14" /> Write review</a>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
    function filterOrders(btn, status){
        document.querySelectorAll('.ut-chip').forEach(c=>c.classList.remove('is-active'));
        btn.classList.add('is-active');
        document.querySelectorAll('.order-row').forEach(function(r){
            r.style.display = (status==='All' || r.dataset.status===status) ? '' : 'none';
        });
    }
</script>
@endpush


