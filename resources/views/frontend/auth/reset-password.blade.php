@extends('frontend.layouts.frontend')
@section('title', 'Reset Password — T-Shirt Shop')
@php $bareLayout = true; @endphp

@section('content')
<div class="ut-auth">
    @include('frontend.auth.partials.brand')
    <div class="ut-auth-form">
        <a href="{{ route('frontend.home') }}" class="ut-link ut-hide-mobile" style="position:absolute;top:28px;right:32px;font-size:13.5px"><x-frontend.icon n="arrowL" :size="15" /> Back to store</a>
        <div style="width:100%;max-width:400px;margin:0 auto">
            <h1 style="font-size:clamp(28px,3vw,34px);margin-bottom:8px">Set a new password</h1>
            <p class="muted" style="margin-bottom:28px;font-size:15px">Choose a strong password you haven't used before.</p>
            <form class="ut-col" style="gap:16px" action="{{ route('frontend.login') }}" method="GET" onsubmit="return checkMatch()">
                <div class="field"><label>New password</label><div style="position:relative"><input class="ut-input" type="password" id="np" placeholder="New password" style="padding-right:64px"><button type="button" data-toggle-pw style="position:absolute;right:12px;top:11px;border:0;background:none;color:var(--text-2);font-family:var(--font-head);font-weight:600;font-size:12.5px">Show</button></div></div>
                <div class="field"><label>Confirm password</label><div style="position:relative"><input class="ut-input" type="password" id="np2" placeholder="Re-enter password" style="padding-right:64px" oninput="matchHint()"><button type="button" data-toggle-pw style="position:absolute;right:12px;top:11px;border:0;background:none;color:var(--text-2);font-family:var(--font-head);font-weight:600;font-size:12.5px">Show</button></div><span id="matchMsg" style="font-size:12.5px;margin-top:6px;font-weight:500"></span></div>
                <button class="ut-btn ut-btn-ink ut-btn-block ut-btn-lg" type="submit">Reset password</button>
            </form>
            <p class="muted" style="text-align:center;margin-top:26px;font-size:14px">Back to <a href="{{ route('frontend.login') }}" style="color:var(--blue);font-weight:600;font-family:var(--font-head)">Sign in</a></p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function matchHint(){
        var a=document.getElementById('np').value, b=document.getElementById('np2').value, m=document.getElementById('matchMsg');
        if(!b){ m.textContent=''; return; }
        if(a===b){ m.textContent='✓ Passwords match'; m.style.color='#15803d'; }
        else { m.textContent="Passwords don't match"; m.style.color='var(--accent)'; }
    }
    function checkMatch(){
        var a=document.getElementById('np').value, b=document.getElementById('np2').value;
        if(a && a===b){ utToast('Password updated'); return true; }
        utToast('Passwords must match'); return false;
    }
</script>
@endpush


