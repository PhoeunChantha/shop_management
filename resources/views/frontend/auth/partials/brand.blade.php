{{-- Auth brand panel (left side of split shell) --}}
<div class="ut-auth-brand">
    <div class="ph" style="position:absolute;inset:0;--ph-tint:linear-gradient(150deg,#1c1d22,#33353c 70%,#43454d)"></div>
    <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(17,24,39,.55),rgba(17,24,39,.82))"></div>
    <a href="{{ route('frontend.home') }}" class="ut-row" style="gap:10px;position:relative;color:#fff">
        <span class="ut-logo-mark" style="background:#fff;color:var(--ink)">T</span>
        <span class="ut-logo-text">T-SHIRT SHOP</span>
    </a>
    <div style="position:relative">
        <span class="ut-eyebrow" style="color:rgba(255,255,255,.8)">Members get more</span>
        <h2 style="color:#fff;font-size:clamp(30px,3vw,44px);line-height:1.05;margin:14px 0 16px;max-width:420px">Premium tees, members-only pricing.</h2>
        <p style="color:rgba(255,255,255,.78);font-size:16px;max-width:380px;line-height:1.6">Early access to every drop, free shipping, and 10% off your first order when you join.</p>
        <div class="ut-row" style="gap:26px;margin-top:30px">
            @foreach([['50k+', 'members'], ['4.9★', 'rating'], ['30-day', 'returns']] as [$a, $b])
                <div><div style="font-family:var(--font-head);font-weight:700;font-size:22px;color:#fff">{{ $a }}</div><div style="opacity:.65;font-size:13px;color:#fff">{{ $b }}</div></div>
            @endforeach
        </div>
    </div>
</div>

