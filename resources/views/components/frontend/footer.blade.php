@php
    $settings = app(\App\Services\Admin\SettingService::class);
    $siteName = $settings->siteName();
    $logo = $settings->logoUrl();
    $tagline = $settings->siteTagline() ?: __('Premium heavyweight tees, built to outlast trends. Designed in studio, made with organic cotton.');

    // Real, admin-configured social links (with URLs). Empty → the row is hidden.
    $socials = collect($settings->socialLinks())
        ->filter(fn ($s) => filled($s['url'] ?? null))
        ->values();

    // Shop column draws real categories from the cached storefront navigation.
    $categoryLinks = collect($frontendNav['categoryMenus'] ?? [])
        ->filter(fn ($m) => str_contains((string) ($m['url'] ?? ''), 'category='))
        ->map(fn ($m) => [$m['label'], $m['url']])
        ->take(3)
        ->values()
        ->all();

    // Help/Brand (and any custom) columns are admin-managed (Settings → Footer menu).
    $managedColumns = collect($settings->footerColumns())
        ->map(fn (array $links): array => array_map(fn (array $l): array => [$l['label'], $l['url']], $links))
        ->all();

    $cols = array_merge([
        'Shop' => array_merge(
            [[__('New Arrivals'), route('frontend.shop.index', ['new' => 1])], [__('Best Sellers'), route('frontend.shop.index', ['best' => 1])]],
            $categoryLinks,
            [[__('Sale'), route('frontend.shop.index', ['sale' => 1])]],
        ),
    ], $managedColumns);
@endphp
<footer class="ut-footer">
    <div class="ut-wrap" style="padding:56px 24px 30px">
        <div class="ut-foot-grid">
            <div>
                <a href="{{ route('frontend.home') }}" class="ut-row" style="gap:10px;margin-bottom:16px;text-decoration:none">
                    @if($logo)
                        <img src="{{ $logo }}" alt="{{ $siteName }}" style="height:34px;width:auto;max-width:170px;object-fit:contain">
                    @else
                        <span class="ut-logo-mark" style="background:#fff;color:var(--ink)">{{ mb_substr($siteName, 0, 1) }}</span>
                        <span class="ut-logo-text" style="color:#fff">{{ mb_strtoupper($siteName) }}</span>
                    @endif
                </a>
                <p style="max-width:300px;color:#94a3b8;font-size:14px;line-height:1.6">{{ $tagline }}</p>
                @if($socials->isNotEmpty())
                    <div class="ut-row" style="gap:10px;margin-top:18px">
                        @foreach($socials as $s)
                            <a href="{{ $s['url'] }}" target="_blank" rel="noopener noreferrer"
                                title="{{ $s['title'] ?? '' }}" aria-label="{{ $s['title'] ?: __('Social link') }}"
                                style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.08);display:grid;place-items:center;color:#fff;text-decoration:none">
                                <i class="{{ $s['icon'] ?: 'fa-solid fa-link' }}"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
            @foreach($cols as $title => $links)
                <div>
                    <h5>{{ __($title) }}</h5>
                    @foreach($links as [$label, $href])
                        <a href="{{ $href }}">{{ $label }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>
        <hr style="border:0;border-top:1px solid rgba(255,255,255,.1);margin:36px 0 20px">
        <div class="ut-row" style="justify-content:space-between;flex-wrap:wrap;gap:12px;font-size:13px;color:#64748b">
            <span>© {{ date('Y') }} {{ $siteName }}. {{ __('All rights reserved.') }}</span>
            <div class="ut-row" style="gap:20px">
                <a href="{{ route('frontend.pages.privacy') }}" style="color:inherit">{{ __('Privacy') }}</a>
                <a href="{{ route('frontend.pages.terms') }}" style="color:inherit">{{ __('Terms') }}</a>
                <a href="{{ route('frontend.pages.privacy') }}" style="color:inherit">{{ __('Cookies') }}</a>
            </div>
        </div>
    </div>
</footer>
