@php $u = app(\App\Services\FrontendAccountService::class)->user(); @endphp
{{-- Mobile profile / account drawer (Bootstrap Offcanvas) --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="profileDrawer" style="width:min(360px,88vw)">
    <div style="background:var(--ink);color:#fff;padding:22px 22px 24px">
        <div class="ut-row" style="justify-content:space-between;margin-bottom:18px">
            <span style="font-family:var(--font-head);font-weight:800;letter-spacing:.12em;font-size:15px">MY ACCOUNT</span>
            <button type="button" data-bs-dismiss="offcanvas" aria-label="Close"
                    style="border:0;background:rgba(255,255,255,.12);color:#fff;width:34px;height:34px;border-radius:50%;display:grid;place-items:center">
                <x-frontend.icon n="close" :size="17" />
            </button>
        </div>
        <div class="ut-row" style="gap:13px">
            <span style="width:48px;height:48px;border-radius:50%;background:#fff;color:var(--ink);display:grid;place-items:center;font-family:var(--font-head);font-weight:700;font-size:19px">{{ $u['first'][0] }}</span>
            <div>
                <div style="font-family:var(--font-head);font-weight:700">{{ $u['name'] }}</div>
                <div style="opacity:.7;font-size:13px">{{ $u['tier'] }} · {{ number_format($u['points']) }} pts</div>
            </div>
        </div>
    </div>

    <div class="offcanvas-body" style="padding:12px">
        @php
            $links = [
                ['Dashboard', 'home', route('frontend.account.dashboard'), null],
                ['My orders', 'box', route('frontend.account.orders'), null],
                ['Wishlist', 'heart', route('frontend.account.wishlist'), 'wish'],
                ['Notifications', 'bell', route('frontend.account.notifications'), null],
                ['Addresses', 'pin', route('frontend.account.addresses'), null],
                ['Profile', 'user', route('frontend.account.profile'), null],
                ['Help center', 'info', route('frontend.pages.faq'), null],
            ];
        @endphp
        @foreach($links as [$label, $ic, $href, $badge])
            <a href="{{ $href }}" class="ut-side-link" style="color:var(--ink)">
                <span style="width:38px;height:38px;border-radius:11px;background:var(--bg);display:grid;place-items:center;color:var(--ink)"><x-frontend.icon :n="$ic" :size="19" /></span>
                {{ $label }}
                @if($badge === 'wish')<span class="ut-badge accent" data-wish-count style="position:static;margin-left:auto;display:none">0</span>@endif
                <x-frontend.icon n="chevR" :size="18" style="margin-left:auto;color:var(--text-3)" />
            </a>
        @endforeach
    </div>

    <div style="padding:16px;border-top:1px solid var(--border)">
        <form method="POST" action="{{ route('frontend.logout') }}">
            @csrf
            <button type="submit" class="ut-btn ut-btn-ghost ut-btn-block"><x-frontend.icon n="arrowL" :size="16" /> Sign out</button>
        </form>
    </div>
</div>

