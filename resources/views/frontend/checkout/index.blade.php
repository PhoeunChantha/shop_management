@extends('frontend.layouts.frontend')
@section('title', 'Checkout — T-Shirt Shop')

@push('head')
<style>
    .ut-checkout-grid { display:grid; grid-template-columns:1fr 380px; gap:40px; align-items:start; }
    .ut-form-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .ut-radio-card{ display:flex;align-items:center;gap:14px;padding:16px 18px;border:1px solid var(--border);border-radius:var(--r-md);cursor:pointer;background:var(--card);transition:border-color .24s cubic-bezier(.22,1,.36,1),background .24s cubic-bezier(.22,1,.36,1); }
    .ut-radio-card.sel{ border-color:var(--ink);background:var(--accent-soft); }
    @media (max-width:1024px){ .ut-checkout-grid{ grid-template-columns:1fr; } .ut-checkout-grid .summary{ position:static !important; } }
    @media (max-width:600px){ .ut-step-label{ display:none; } }
</style>
@endpush

@section('content')
<div class="ut-wrap anim-up" style="padding-top:28px">
    <a href="{{ route('frontend.cart.index') }}" class="ut-link" style="margin-bottom:18px;display:inline-flex"><x-frontend.icon n="arrowL" :size="16" /> Back to bag</a>
    <form class="ut-checkout-grid" id="checkoutForm" method="POST" action="{{ route('frontend.checkout.store') }}">
        @csrf
        <input type="hidden" name="items" id="coItems">
        <input type="hidden" name="payment" id="coPayment" value="{{ $paymentMethods[0]['code'] ?? 'card' }}">
        <div>
            <h1 style="font-size:clamp(28px,3.4vw,40px);margin-bottom:22px">Checkout</h1>

            {{-- step indicator --}}
            <div class="ut-row" style="gap:6px;margin-bottom:28px">
                @foreach(['Contact & Shipping', 'Delivery', 'Payment', 'Review'] as $i => $label)
                    <div class="ut-row" style="gap:8px">
                        <span data-step-dot style="width:30px;height:30px;border-radius:50%;display:grid;place-items:center;font-family:var(--font-head);font-weight:700;font-size:13px;background:{{ $i === 0 ? 'var(--ink)' : 'var(--bg)' }};color:{{ $i === 0 ? '#fff' : 'var(--text-2)' }};border:{{ $i === 0 ? 'none' : '1px solid var(--border)' }}">{{ $i + 1 }}</span>
                        <span class="ut-step-label" style="font-family:var(--font-head);font-weight:600;font-size:13.5px;color:{{ $i === 0 ? 'var(--ink)' : 'var(--text-2)' }};white-space:nowrap">{{ $label }}</span>
                    </div>
                    @if($i < 3)<div style="flex:1;height:2px;background:var(--border);min-width:14px"></div>@endif
                @endforeach
            </div>

            <div class="ut-card" style="padding:28px" id="checkoutSteps">
                {{-- STEP 1 — contact & shipping --}}
                <div data-step>
                    <h3 style="font-size:20px;margin-bottom:20px">Contact & Shipping</h3>
                    <div class="ut-col" style="gap:16px">
                        <div class="field"><label>Email address</label><input class="ut-input" type="email" name="email" placeholder="you@email.com" required></div>
                        <div class="ut-form-2">
                            <div class="field"><label>First name</label><input class="ut-input" name="first_name" placeholder="Alex" required></div>
                            <div class="field"><label>Last name</label><input class="ut-input" name="last_name" placeholder="Rivera" required></div>
                        </div>
                        <div class="field"><label>Street address</label><input class="ut-input" name="address" placeholder="123 Market St, Apt 4B" required></div>
                        <div class="ut-form-2" style="grid-template-columns:1.6fr 1fr">
                            <div class="field"><label>City</label><input class="ut-input" name="city" placeholder="San Francisco" required></div>
                            <div class="field"><label>ZIP code</label><input class="ut-input" name="zip" placeholder="94103"></div>
                        </div>
                    </div>
                </div>

                {{-- STEP 2 — delivery --}}
                <div data-step style="display:none">
                    <h3 style="font-size:20px;margin-bottom:20px">Delivery</h3>
                    <div class="ut-col" style="gap:12px">
                        @php($shippingMethods = $shippingMethods ?? [])
                        @forelse($shippingMethods as $i => $m)
                            @php($label = $m['type'] === 'free' ? 'Free' : '$'.number_format($m['rate'], 2))
                            <label class="ut-radio-card {{ $i === 0 ? 'sel' : '' }}" onclick="document.querySelectorAll('.ut-radio-card').forEach(c=>c.classList.remove('sel')); this.classList.add('sel');">
                                <input type="radio" name="del" value="{{ $m['id'] }}" {{ $i === 0 ? 'checked' : '' }}
                                    onchange="window.__coRecalc && window.__coRecalc()" style="accent-color:var(--blue);width:18px;height:18px">
                                <div style="flex:1"><div style="font-family:var(--font-head);font-weight:600">{{ $m['name'] }}</div><div class="muted" style="font-size:13px">{{ $m['description'] ?: 'Standard delivery' }}@if($m['type'] === 'free_over') · free over ${{ number_format($m['free_over'], 0) }}@endif</div></div>
                                <span style="font-family:var(--font-head);font-weight:700;color:{{ $m['type'] === 'free' ? '#15803d' : 'var(--ink)' }}">{{ $label }}</span>
                            </label>
                        @empty
                            <label class="ut-radio-card sel">
                                <input type="radio" name="del" value="0" checked onchange="window.__coRecalc && window.__coRecalc()" style="accent-color:var(--blue);width:18px;height:18px">
                                <div style="flex:1"><div style="font-family:var(--font-head);font-weight:600">Standard</div><div class="muted" style="font-size:13px">2–4 business days</div></div>
                                <span style="font-family:var(--font-head);font-weight:700;color:#15803d">Free</span>
                            </label>
                        @endforelse
                    </div>
                </div>

                {{-- STEP 3 — payment --}}
                <div data-step style="display:none">
                    <h3 style="font-size:20px;margin-bottom:20px">Payment</h3>
                    <div class="ut-col" style="gap:16px">
                        <div class="ut-row" style="gap:10px;flex-wrap:wrap">
                            @php($paymentMethods = $paymentMethods ?? [])
                            @forelse($paymentMethods as $i => $p)
                                <button type="button" class="pay-tab" data-pay="{{ $p['code'] }}" data-type="{{ $p['type'] }}"
                                        onclick="selectPayment(this)"
                                        style="flex:1;min-width:96px;padding:14px;border-radius:var(--r-md);border:1.5px solid {{ $i === 0 ? 'var(--ink)' : 'var(--border)' }};background:#fff;display:flex;flex-direction:column;align-items:center;gap:7px;font-family:var(--font-head);font-weight:600;font-size:13px">
                                    @if(!empty($p['image']))
                                        <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" style="height:22px;max-width:60px;object-fit:contain">
                                    @else
                                        <x-frontend.icon :n="$p['type'] === 'manual' ? 'lock' : 'card'" :size="22" />
                                    @endif
                                    {{ $p['name'] }}
                                </button>
                            @empty
                                <button type="button" class="pay-tab" style="flex:1;padding:14px;border-radius:var(--r-md);border:1.5px solid var(--ink);background:#fff;display:flex;flex-direction:column;align-items:center;gap:7px;font-family:var(--font-head);font-weight:600;font-size:13px">
                                    <x-frontend.icon n="card" :size="22" />Card
                                </button>
                            @endforelse
                        </div>
                        @php($firstPay = $paymentMethods[0] ?? ['code' => 'card', 'type' => 'online'])
                        {{-- Online / card fields --}}
                        <div data-pay-online style="{{ ($firstPay['type'] ?? 'online') === 'manual' ? 'display:none' : '' }}">
                            <div class="ut-col" style="gap:16px">
                                <div class="field"><label>Card number</label><div style="position:relative"><input class="ut-input" placeholder="4242 4242 4242 4242" style="padding-right:42px"><span style="position:absolute;right:14px;top:14px;color:var(--text-2)"><x-frontend.icon n="card" :size="18" /></span></div></div>
                                <div class="ut-form-2">
                                    <div class="field"><label>Expiry</label><input class="ut-input" placeholder="MM / YY"></div>
                                    <div class="field"><label>CVC</label><input class="ut-input" placeholder="123"></div>
                                </div>
                                <div class="ut-row muted" style="gap:8px;font-size:13px"><span style="color:var(--success)"><x-frontend.icon n="lock" :size="15" /></span> Payments are encrypted and secure.</div>
                            </div>
                        </div>

                        {{-- Manual / QR payment instructions --}}
                        @foreach($paymentMethods as $p)
                            @if($p['type'] === 'manual')
                                <div data-pay-panel="{{ $p['code'] }}" style="display:{{ ($firstPay['code'] ?? '') === $p['code'] ? 'block' : 'none' }}">
                                    @if($p['description'])<p class="muted" style="font-size:13.5px;margin:0 0 12px">{{ $p['description'] }}</p>@endif
                                    @if($p['qr_image'])
                                        <div style="text-align:center;margin-bottom:14px"><img src="{{ $p['qr_image'] }}" alt="Payment QR" style="width:180px;height:180px;object-fit:contain;border:1px solid var(--border);border-radius:12px;padding:8px;background:#fff"></div>
                                    @endif
                                    @if($p['bank_name'] || $p['account_name'] || $p['account_number'])
                                        <div style="background:var(--bg);border-radius:var(--r-md);padding:14px 16px">
                                            @if($p['bank_name'])<div class="ut-row" style="justify-content:space-between;font-size:13.5px;padding:4px 0"><span class="muted">Bank</span><b>{{ $p['bank_name'] }}</b></div>@endif
                                            @if($p['account_name'])<div class="ut-row" style="justify-content:space-between;font-size:13.5px;padding:4px 0"><span class="muted">Account name</span><b>{{ $p['account_name'] }}</b></div>@endif
                                            @if($p['account_number'])<div class="ut-row" style="justify-content:space-between;font-size:13.5px;padding:4px 0"><span class="muted">Account no.</span><b class="mono">{{ $p['account_number'] }}</b></div>@endif
                                        </div>
                                    @endif
                                    @if($p['instructions'])<p class="muted" style="font-size:13px;line-height:1.65;margin:12px 0 0">{{ $p['instructions'] }}</p>@endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- STEP 4 — review --}}
                <div data-step style="display:none">
                    <h3 style="font-size:20px;margin-bottom:20px">Review your order</h3>
                    <div class="ut-col" style="gap:0">
                        @foreach([['Contact', 'you@email.com'], ['Ship to', 'Alex Rivera, 123 Market St'], ['Delivery', 'Standard (Free)'], ['Payment', 'Card •••• 4242']] as [$k, $v])
                            <div class="ut-row" style="justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-2)"><span class="muted" style="font-size:14px">{{ $k }}</span><span style="font-family:var(--font-head);font-weight:600;font-size:14px;text-align:right">{{ $v }}</span></div>
                        @endforeach
                        <label class="ut-row" style="gap:10px;font-size:14px;margin-top:16px"><input type="checkbox" checked style="accent-color:var(--blue);width:17px;height:17px"> Email me order updates & early drop access</label>
                    </div>
                </div>

                {{-- nav buttons --}}
                <div class="ut-row" style="justify-content:space-between;margin-top:26px;gap:12px">
                    <button type="button" class="ut-btn ut-btn-ghost" id="coBack" style="visibility:hidden"><x-frontend.icon n="arrowL" :size="16" /> Back</button>
                    <button type="button" class="ut-btn ut-btn-ink ut-btn-lg" id="coNext">Continue <x-frontend.icon n="arrowR" :size="16" /></button>
                </div>
            </div>
        </div>

        {{-- summary --}}
        <div class="ut-card summary" style="padding:24px;position:sticky;top:96px">
            <h3 style="font-size:18px;margin-bottom:16px">Order summary</h3>
            <div id="checkoutLines" style="max-height:230px;overflow:auto;margin-bottom:14px"></div>
            <hr class="divider" style="margin:6px 0 14px">
            <div class="ut-col" style="gap:11px">
                <div class="ut-row" style="justify-content:space-between;font-size:14.5px"><span class="muted">Subtotal</span><span id="sumSubtotal" style="font-weight:600">$0</span></div>
                <div class="ut-row" style="justify-content:space-between;font-size:14.5px"><span class="muted">Shipping</span><span id="sumShipping" style="font-weight:600">Free</span></div>
                <div class="ut-row" style="justify-content:space-between;font-size:14.5px"><span class="muted">Estimated tax</span><span id="sumTax" style="font-weight:600">$0</span></div>
                <hr class="divider" style="margin:6px 0">
                <div class="ut-row" style="justify-content:space-between"><span style="font-family:var(--font-head);font-weight:700;font-size:17px">Total</span><span id="sumTotal" style="font-family:var(--font-head);font-weight:700;font-size:24px">$0</span></div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script>window.UT_CHECKOUT = { shipping: @json($shippingMethods ?? []), taxRate: {{ $taxRate ?? 0 }} };</script>
    <script>
        // Payment tab select — highlight, set hidden payment code, show card fields
        // for online methods or the matching manual/QR panel for manual methods.
        window.selectPayment = function (btn) {
            document.querySelectorAll('.pay-tab').forEach(function (b) { b.style.borderColor = 'var(--border)'; });
            btn.style.borderColor = 'var(--ink)';
            var pi = document.getElementById('coPayment');
            if (pi) pi.value = btn.dataset.pay;

            var manual = btn.dataset.type === 'manual';
            var online = document.querySelector('[data-pay-online]');
            if (online) online.style.display = manual ? 'none' : '';
            document.querySelectorAll('[data-pay-panel]').forEach(function (p) {
                p.style.display = (manual && p.getAttribute('data-pay-panel') === btn.dataset.pay) ? 'block' : 'none';
            });
        };
    </script>
    <script>
        // Checkout summary — totals reflect admin shipping methods + tax rate.
        (function(){
            var wrap = document.getElementById('checkoutLines');
            var colors = window.UT_COLORS || {};
            function cart(){ try { return JSON.parse(localStorage.getItem('ut_cart')||'[]'); } catch(e){ return []; } }
            function money(n){ return '$'+(Math.round(n*100)/100).toFixed(2); }

            function renderLines(items){
                if(!wrap) return;
                if(!items.length){ wrap.innerHTML='<p class="muted" style="font-size:14px;text-align:center;padding:20px 0">Your bag is empty. <a href="{{ route('frontend.shop.index') }}" style="color:var(--blue);font-weight:600">Shop tees</a></p>'; return; }
                wrap.innerHTML = items.map(function(it){
                    var cn = (colors[it.color]||{}).name || it.color;
                    var img = it.image ? '<img src="'+it.image+'" alt="" style="width:54px;height:66px;border-radius:10px;object-fit:cover">'
                                    : '<div class="ph" style="width:54px;height:66px;border-radius:10px;--ph-tint:'+it.tint+'"></div>';
                    return '<div class="ut-row" style="gap:12px;padding:8px 0"><div style="position:relative;flex-shrink:0">'+img+
                        '<span class="ut-badge" style="top:-6px;right:-6px">'+it.qty+'</span></div>'+
                        '<div style="flex:1;min-width:0"><div style="font-family:var(--font-head);font-weight:600;font-size:13.5px">'+it.name+'</div>'+
                        '<div class="muted" style="font-size:12px">'+it.size+' · '+cn+'</div></div>'+
                        '<span style="font-family:var(--font-head);font-weight:600;font-size:13.5px">'+money(it.price*it.qty)+'</span></div>';
                }).join('');
            }

            window.__coRecalc = function(){
                var items = cart();
                renderLines(items);
                var sub = items.reduce(function(s,i){ return s + i.price*i.qty; }, 0);
                var cfg = window.UT_CHECKOUT || { shipping:[], taxRate:0 };
                var selEl = document.querySelector('input[name="del"]:checked');
                var method = null;
                if(selEl){ method = (cfg.shipping||[]).find(function(m){ return String(m.id)===String(selEl.value); }); }
                if(!method){ method = (cfg.shipping||[])[0]; }
                var shipping = 0;
                if(method){
                    if(method.type==='free') shipping = 0;
                    else if(method.type==='free_over') shipping = (method.free_over!=null && sub>=method.free_over) ? 0 : method.rate;
                    else shipping = method.rate;
                }
                var tax = Math.round(sub*(cfg.taxRate||0)*100)/100;
                var total = sub + shipping + tax;
                var g = function(id){ return document.getElementById(id); };
                if(g('sumSubtotal')) g('sumSubtotal').textContent = money(sub);
                if(g('sumShipping')) g('sumShipping').textContent = shipping===0 ? 'Free' : money(shipping);
                if(g('sumTax')) g('sumTax').textContent = money(tax);
                if(g('sumTotal')) g('sumTotal').textContent = money(total);
            };

            window.__coRecalc();
        })();
    </script>
@endpush


