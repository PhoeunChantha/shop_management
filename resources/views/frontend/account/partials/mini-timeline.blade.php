@php $stages = ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered']; @endphp
<div class="ut-row" style="gap:6px">
    @foreach($stages as $i => $s)
        <div style="flex:1">
            <div style="height:5px;border-radius:5px;background:{{ $i < $stage ? 'var(--success)' : 'var(--border)' }}"></div>
            <div style="font-size:11px;margin-top:6px;font-family:var(--font-head);font-weight:600;color:{{ $i < $stage ? 'var(--ink)' : 'var(--text-3)' }}">{{ $s }}</div>
        </div>
    @endforeach
</div>

