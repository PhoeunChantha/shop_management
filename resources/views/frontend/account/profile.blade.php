@extends('frontend.account.partials.shell', ['active' => 'profile'])
@section('title', 'Profile — T-Shirt Shop')

@section('account')
<h2 style="font-size:24px;margin-bottom:4px">Profile</h2>
<p class="muted" style="font-size:14px;margin-bottom:18px">Manage your personal information</p>
<div class="ut-card" style="padding:26px">
    <div class="ut-row" style="gap:16px;margin-bottom:24px;padding-bottom:22px;border-bottom:1px solid var(--border)">
        <span style="width:72px;height:72px;border-radius:50%;background:var(--ink);color:#fff;display:grid;place-items:center;font-family:var(--font-head);font-weight:700;font-size:28px">{{ $user['first'][0] }}</span>
        <div><button type="button" class="ut-btn ut-btn-ghost ut-btn-sm">Change photo</button><p class="muted" style="font-size:12.5px;margin-top:8px">JPG or PNG, max 2MB</p></div>
    </div>
    <form class="ut-col" style="gap:16px;max-width:540px" onsubmit="event.preventDefault(); utToast('Profile updated');">
        <div class="ut-form-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="field"><label>First name</label><input class="ut-input" value="{{ $user['first'] }}"></div>
            <div class="field"><label>Last name</label><input class="ut-input" value="{{ $user['last'] }}"></div>
        </div>
        <div class="field"><label>Email address</label><input class="ut-input" type="email" value="{{ $user['email'] }}"></div>
        <div class="field"><label>Phone number</label><input class="ut-input" value="{{ $user['phone'] }}"></div>
        <div class="ut-row" style="gap:10px;margin-top:4px"><button class="ut-btn ut-btn-ink" type="submit">Save changes</button><button class="ut-btn ut-btn-ghost" type="button">Cancel</button></div>
    </form>
</div>
@endsection

