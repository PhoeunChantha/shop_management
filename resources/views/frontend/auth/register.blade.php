@extends('frontend.layouts.frontend')
@section('title', 'Create Account — T-Shirt Shop')
@php $bareLayout = true; @endphp

@section('content')
<div class="ut-auth">
    @include('frontend.auth.partials.brand')
    <div class="ut-auth-form">
        <a href="{{ route('frontend.home') }}" class="ut-link ut-hide-mobile" style="position:absolute;top:28px;right:32px;font-size:13.5px"><x-frontend.icon n="arrowL" :size="15" /> Back to store</a>
        <div style="width:100%;max-width:400px;margin:0 auto">
            <h1 style="font-size:clamp(28px,3vw,34px);margin-bottom:8px">Create your account</h1>
            <p class="muted" style="margin-bottom:28px;font-size:15px">Join T-Shirt Shop and get 10% off your first order.</p>

            {{-- social login (managed in Settings → Login) --}}
            @include('frontend.auth.partials.social')

            <form class="ut-col" style="gap:16px" action="{{ route('frontend.otp.verify') }}" method="GET">
                <div class="ut-form-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="field"><label>First name</label><input class="ut-input" placeholder="Alex"></div>
                    <div class="field"><label>Last name</label><input class="ut-input" placeholder="Rivera"></div>
                </div>
                <div class="field"><label>Email address</label><input class="ut-input" type="email" placeholder="you@email.com"></div>
                <div class="field">
                    <label>Password</label>
                    <div style="position:relative"><input class="ut-input" type="password" id="regPw" placeholder="Create a password" style="padding-right:64px" oninput="pwStrength(this.value)"><button type="button" data-toggle-pw style="position:absolute;right:12px;top:11px;border:0;background:none;color:var(--text-2);font-family:var(--font-head);font-weight:600;font-size:12.5px">Show</button></div>
                    <div id="pwMeter" style="display:none;margin-top:8px">
                        <div class="ut-row" style="gap:5px">@for($i=0;$i<4;$i++)<div class="pw-bar" style="flex:1;height:4px;border-radius:4px;background:var(--border)"></div>@endfor</div>
                        <span id="pwLabel" style="font-size:12px;font-family:var(--font-head);font-weight:600"></span>
                    </div>
                </div>
                <label class="ut-row" style="gap:9px;font-size:13.5px;align-items:flex-start"><input type="checkbox" required style="accent-color:var(--blue);width:16px;height:16px;margin-top:2px"> <span class="muted">I agree to the <a href="{{ route('frontend.pages.terms') }}" style="color:var(--blue);font-weight:600">Terms</a> and <a href="{{ route('frontend.pages.privacy') }}" style="color:var(--blue);font-weight:600">Privacy Policy</a>.</span></label>
                <button class="ut-btn ut-btn-ink ut-btn-block ut-btn-lg" type="submit">Create account</button>
            </form>
            <p class="muted" style="text-align:center;margin-top:26px;font-size:14px">Already have an account? <a href="{{ route('frontend.login') }}" style="color:var(--blue);font-weight:600;font-family:var(--font-head)">Sign in</a></p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function pwStrength(v){
        var meter = document.getElementById('pwMeter');
        if(!v){ meter.style.display='none'; return; }
        meter.style.display='block';
        var s = (v.length>=8?1:0)+(/[A-Z]/.test(v)?1:0)+(/[0-9]/.test(v)?1:0)+(/[^A-Za-z0-9]/.test(v)?1:0);
        var cols=['','#ef4444','#f97316','#eab308','#22c55e'], labels=['','Weak','Fair','Good','Strong'];
        document.querySelectorAll('.pw-bar').forEach(function(b,i){ b.style.background = i<s ? cols[s] : 'var(--border)'; });
        var lbl=document.getElementById('pwLabel'); lbl.textContent=labels[s]+' password'; lbl.style.color=cols[s]||'var(--text-2)';
    }
</script>
@endpush


