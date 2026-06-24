@php
    $cols = [
        'Shop' => [['New Arrivals', route('frontend.shop.index')], ['Best Sellers', route('frontend.shop.index')], ['Heavyweight', route('frontend.shop.index')], ['Graphic', route('frontend.shop.index')], ['Sale', route('frontend.shop.index')]],
        'Help' => [['Shipping', route('frontend.pages.faq')], ['Returns', route('frontend.pages.faq')], ['Size Guide', route('frontend.pages.faq')], ['Track Order', route('frontend.account.orders')], ['Contact', route('frontend.pages.contact')]],
        'Brand' => [['Our Story', route('frontend.pages.about')], ['Sustainability', route('frontend.pages.about')], ['FAQ', route('frontend.pages.faq')], ['Contact', route('frontend.pages.contact')], ['Stores', route('frontend.pages.contact')]],
    ];
@endphp
<footer class="ut-footer">
    <div class="ut-wrap" style="padding:56px 24px 30px">
        <div class="ut-foot-grid">
            <div>
                <div class="ut-row" style="gap:10px;margin-bottom:16px">
                    <span class="ut-logo-mark" style="background:#fff;color:var(--ink)">T</span>
                    <span class="ut-logo-text" style="color:#fff">T-SHIRT SHOP</span>
                </div>
                <p style="max-width:300px;color:#94a3b8;font-size:14px;line-height:1.6">Premium heavyweight tees, built to outlast trends. Designed in studio, made with organic cotton.</p>
                <div class="ut-row" style="gap:10px;margin-top:18px">
                    @foreach(['ig', 'share', 'mail'] as $ic)
                        <span style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.08);display:grid;place-items:center;color:#fff"><x-frontend.icon :n="$ic" :size="18" /></span>
                    @endforeach
                </div>
            </div>
            @foreach($cols as $title => $links)
                <div>
                    <h5>{{ $title }}</h5>
                    @foreach($links as [$label, $href])
                        <a href="{{ $href }}">{{ $label }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>
        <hr style="border:0;border-top:1px solid rgba(255,255,255,.1);margin:36px 0 20px">
        <div class="ut-row" style="justify-content:space-between;flex-wrap:wrap;gap:12px;font-size:13px;color:#64748b">
            <span>© {{ date('Y') }} T-Shirt Shop. All rights reserved.</span>
            <div class="ut-row" style="gap:20px">
                <a href="{{ route('frontend.pages.privacy') }}" style="color:inherit">Privacy</a>
                <a href="{{ route('frontend.pages.terms') }}" style="color:inherit">Terms</a>
                <a href="{{ route('frontend.pages.privacy') }}" style="color:inherit">Cookies</a>
            </div>
        </div>
    </div>
</footer>

