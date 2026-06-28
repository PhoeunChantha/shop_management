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
    <div class="ut-checkout-grid">
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
                        <div class="field"><label>Email address</label><input class="ut-input" type="email" placeholder="you@email.com"></div>
                        <div class="ut-form-2">
                            <div class="field"><label>First name</label><input class="ut-input" placeholder="Alex"></div>
                            <div class="field"><label>Last name</label><input class="ut-input" placeholder="Rivera"></div>
                        </div>
                        <div class="field"><label>Street address</label><input class="ut-input" placeholder="123 Market St, Apt 4B"></div>
                        <div class="ut-form-2" style="grid-template-columns:1.6fr 1fr">
                            <div class="field"><label>City</label><input class="ut-input" placeholder="San Francisco"></div>
                            <div class="field"><label>ZIP code</label><input class="ut-input" placeholder="94103"></div>
                        </div>
                    </div>
                </div>

                {{-- STEP 2 — delivery --}}
                <div data-step style="display:none">
                    <h3 style="font-size:20px;margin-bottom:20px">Delivery</h3>
                    <div class="ut-col" style="gap:12px">
                        @foreach([['Standard', '2–4 business days', 'Free', true], ['Express', '1–2 business days', '$14.95', false], ['Store pickup', 'Ready in 2 hours', 'Free', false]] as [$t, $d, $price, $sel])
                            <label class="ut-radio-card {{ $sel ? 'sel' : '' }}" onclick="document.querySelectorAll('.ut-radio-card').forEach(c=>c.classList.remove('sel')); this.classList.add('sel');">
                                <input type="radio" name="del" {{ $sel ? 'checked' : '' }} style="accent-color:var(--blue);width:18px;height:18px">
                                <div style="flex:1"><div style="font-family:var(--font-head);font-weight:600">{{ $t }}</div><div class="muted" style="font-size:13px">{{ $d }}</div></div>
                                <span style="font-family:var(--font-head);font-weight:700;color:{{ $price === 'Free' ? '#15803d' : 'var(--ink)' }}">{{ $price }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- STEP 3 — payment --}}
                <div data-step style="display:none">
                    <h3 style="font-size:20px;margin-bottom:20px">Payment</h3>
                    <div class="ut-col" style="gap:16px">
                        <div class="ut-row" style="gap:10px">
                            @foreach([['Card', 'card', true], ['Apple Pay', 'lock', false], ['Google Pay', 'lock', false]] as [$t, $ic, $sel])
                                <button type="button" class="pay-tab" onclick="document.querySelectorAll('.pay-tab').forEach(b=>b.style.borderColor='var(--border)'); this.style.borderColor='var(--ink)';"
                                        style="flex:1;padding:14px;border-radius:var(--r-md);border:1.5px solid {{ $sel ? 'var(--ink)' : 'var(--border)' }};background:#fff;display:flex;flex-direction:column;align-items:center;gap:7px;font-family:var(--font-head);font-weight:600;font-size:13px">
                                    <x-frontend.icon :n="$ic" :size="22" />{{ $t }}
                                </button>
                            @endforeach
                        </div>
                        <div class="field"><label>Card number</label><div style="position:relative"><input class="ut-input" placeholder="4242 4242 4242 4242" style="padding-right:42px"><span style="position:absolute;right:14px;top:14px;color:var(--text-2)"><x-frontend.icon n="card" :size="18" /></span></div></div>
                        <div class="ut-form-2">
                            <div class="field"><label>Expiry</label><input class="ut-input" placeholder="MM / YY"></div>
                            <div class="field"><label>CVC</label><input class="ut-input" placeholder="123"></div>
                        </div>
                        <div class="ut-row muted" style="gap:8px;font-size:13px"><span style="color:var(--success)"><x-frontend.icon n="lock" :size="15" /></span> Payments are encrypted and secure.</div>
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
    </div>
</div>
@endsection

@push('scripts')
<script>
    // hydrate checkout summary lines from localStorage cart
    (function(){
        var cart = []; try { cart = JSON.parse(localStorage.getItem('ut_cart')||'[]'); } catch(e){}
        var wrap = document.getElementById('checkoutLines');
        var colors = window.UT_COLORS || {};
        if(wrap && cart.length){
            wrap.innerHTML = cart.map(function(it){
                var cn = (colors[it.color]||{}).name || it.color;
                return '<div class="ut-row" style="gap:12px;padding:8px 0">'+
                    '<div style="position:relative;flex-shrink:0"><div class="ph" style="width:54px;height:66px;border-radius:10px;--ph-tint:'+it.tint+'"></div>'+
                    '<span class="ut-badge" style="top:-6px;right:-6px">'+it.qty+'</span></div>'+
                    '<div style="flex:1;min-width:0"><div style="font-family:var(--font-head);font-weight:600;font-size:13.5px">'+it.name+'</div>'+
                    '<div class="muted" style="font-size:12px">'+it.size+' · '+cn+'</div></div>'+
                    '<span style="font-family:var(--font-head);font-weight:600;font-size:13.5px">$'+(it.price*it.qty)+'</span></div>';
            }).join('');
            var sub = cart.reduce(function(s,i){return s+i.price*i.qty;},0);
            var shipping = sub>=75?0:6.95, tax=Math.round(sub*0.08*100)/100, total=sub+shipping+tax;
            document.getElementById('sumSubtotal').textContent='$'+sub;
            document.getElementById('sumShipping').textContent=shipping===0?'Free':'$'+shipping.toFixed(2);
            document.getElementById('sumTax').textContent='$'+tax.toFixed(2);
            document.getElementById('sumTotal').textContent='$'+total.toFixed(2);
        } else if(wrap){
            wrap.innerHTML='<p class="muted" style="font-size:14px;text-align:center;padding:20px 0">Your bag is empty. <a href="{{ route('frontend.shop.index') }}" style="color:var(--blue);font-weight:600">Shop tees</a></p>';
        }
    })();
</script>
@endpush


