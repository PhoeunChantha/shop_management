{{-- Vertical order progress. $steps from OrderTrackingService::steps(). --}}
<div style="position:relative;padding-left:6px">
    @foreach($steps as $step)
        <div class="ut-row" style="gap:18px;align-items:flex-start;{{ ! $loop->last ? 'padding-bottom:28px' : '' }};position:relative">
            @if(! $loop->last)<div style="position:absolute;left:20px;top:40px;bottom:0;width:2px;background:{{ $step['done'] ? 'var(--success)' : 'var(--border)' }}"></div>@endif
            <span style="width:42px;height:42px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;z-index:1;background:{{ $step['done'] ? 'var(--success)' : '#fff' }};color:{{ $step['done'] ? '#fff' : 'var(--text-3)' }};border:{{ $step['done'] ? 'none' : '2px solid var(--border)' }};{{ $step['current'] ? 'box-shadow:0 0 0 5px #dcfce7' : '' }}"><x-frontend.icon :n="$step['icon']" :size="20" /></span>
            <div style="padding-top:3px">
                <div class="ut-row" style="gap:10px;flex-wrap:wrap">
                    <span style="font-family:var(--font-head);font-weight:700;font-size:15.5px;color:{{ $step['done'] ? 'var(--ink)' : 'var(--text-2)' }}">{{ $step['label'] }}</span>
                    @if($step['current'])<span class="ut-tag ut-tag-new" style="padding:2px 9px">{{ __('Current') }}</span>@endif
                </div>
                <p class="muted" style="font-size:13.5px;margin:4px 0 0">{{ $step['desc'] }}</p>
                @if($step['date'])<span style="font-size:12.5px;color:var(--text-3);font-family:var(--font-head);font-weight:600">{{ $step['date'] }}</span>@endif
            </div>
        </div>
    @endforeach
</div>
