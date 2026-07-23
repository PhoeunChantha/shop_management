{{-- Auth brand panel (left side of split shell) — content + background
     managed in Settings → Login. --}}
@inject('settingsService', 'App\Services\SettingService')
@php($login = $settingsService->loginPage())

<div class="ut-auth-brand">
    @if ($login['bg'])
        <div style="position:absolute;inset:0;background:url('{{ $login['bg'] }}') center/cover no-repeat"></div>
    @else
        <div class="ph" style="position:absolute;inset:0;--ph-tint:linear-gradient(150deg,#1c1d22,#33353c 70%,#43454d)"></div>
    @endif
    <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(17,24,39,.55),rgba(17,24,39,.82))"></div>
    <a href="{{ route('frontend.home') }}" class="ut-row" style="gap:10px;position:relative;color:#fff">
        <span class="ut-logo-mark" style="background:#fff;color:var(--ink)">T</span>
        <span class="ut-logo-text">T-SHIRT SHOP</span>
    </a>
    <div style="position:relative">
        <span class="ut-eyebrow" style="color:rgba(255,255,255,.8)">{{ $login['kicker'] }}</span>
        <h2 style="color:#fff;font-size:clamp(30px,3vw,44px);line-height:1.05;margin:14px 0 16px;max-width:420px">{{ $login['title'] }}</h2>
        <p style="color:rgba(255,255,255,.78);font-size:16px;max-width:380px;line-height:1.6">{{ $login['subtitle'] }}</p>
        <div class="ut-row" style="gap:26px;margin-top:30px">
            @foreach([['50k+', 'members'], ['4.9★', 'rating'], ['30-day', 'returns']] as [$a, $b])
                <div><div style="font-family:var(--font-head);font-weight:700;font-size:22px;color:#fff">{{ $a }}</div><div style="opacity:.65;font-size:13px;color:#fff">{{ $b }}</div></div>
            @endforeach
        </div>
    </div>
</div>
