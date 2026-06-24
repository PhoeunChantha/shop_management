@extends('frontend.account.partials.shell', ['active' => 'password'])
@section('title', 'Password & Security — T-Shirt Shop')

@section('account')
<h2 style="font-size:24px;margin-bottom:4px">Password &amp; security</h2>
<p class="muted" style="font-size:14px;margin-bottom:18px">Keep your account secure</p>
<div class="ut-card" style="padding:26px;max-width:540px;margin-bottom:18px">
    <form class="ut-col" style="gap:16px" onsubmit="event.preventDefault(); utToast('Password changed'); this.reset();">
        <div class="field"><label>Current password</label><div style="position:relative"><input class="ut-input" type="password" placeholder="Current password" style="padding-right:64px"><button type="button" data-toggle-pw style="position:absolute;right:12px;top:11px;border:0;background:none;color:var(--text-2);font-family:var(--font-head);font-weight:600;font-size:12.5px">Show</button></div></div>
        <div class="field"><label>New password</label><div style="position:relative"><input class="ut-input" type="password" placeholder="New password" style="padding-right:64px"><button type="button" data-toggle-pw style="position:absolute;right:12px;top:11px;border:0;background:none;color:var(--text-2);font-family:var(--font-head);font-weight:600;font-size:12.5px">Show</button></div></div>
        <div class="field"><label>Confirm new password</label><div style="position:relative"><input class="ut-input" type="password" placeholder="Re-enter new password" style="padding-right:64px"><button type="button" data-toggle-pw style="position:absolute;right:12px;top:11px;border:0;background:none;color:var(--text-2);font-family:var(--font-head);font-weight:600;font-size:12.5px">Show</button></div></div>
        <button class="ut-btn ut-btn-ink" type="submit" style="align-self:flex-start">Update password</button>
    </form>
</div>
<div class="ut-card" style="padding:22px">
    <div class="ut-row" style="justify-content:space-between;gap:12px">
        <div class="ut-row" style="gap:14px">
            <span style="width:44px;height:44px;border-radius:12px;background:#dcfce7;color:#15803d;display:grid;place-items:center"><x-frontend.icon n="shield" :size="20" /></span>
            <div><div style="font-family:var(--font-head);font-weight:600">Two-factor authentication</div><div class="muted" style="font-size:13px">Add an extra layer of security</div></div>
        </div>
        <button type="button" class="ut-btn ut-btn-ghost ut-btn-sm" onclick="utToast('2FA setup started')">Enable</button>
    </div>
</div>
@endsection

