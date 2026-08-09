@extends('frontend.account.partials.shell', ['active' => 'password'])
@section('title', __('Password & Security').' — T-Shirt Shop')

@section('account')
<h2 style="font-size:24px;margin-bottom:4px">{{ __('Password & security') }}</h2>
<p class="muted" style="font-size:14px;margin-bottom:18px">{{ __('Keep your account secure') }}</p>
<div class="ut-card" style="padding:26px;max-width:540px;margin-bottom:18px">
    <form class="ut-col" style="gap:16px" method="POST" action="{{ route('frontend.account.password.update') }}">
        @csrf
        @method('PUT')
        <div class="field"><label>{{ __('Current password') }}</label><div style="position:relative"><input class="ut-input" type="password" name="current_password" placeholder="{{ __('Current password') }}" style="padding-right:64px" required><button type="button" data-toggle-pw style="position:absolute;right:12px;top:11px;border:0;background:none;color:var(--text-2);font-family:var(--font-head);font-weight:600;font-size:12.5px">{{ __('Show') }}</button></div>
            @error('current_password')<span style="color:var(--accent);font-size:12.5px;margin-top:6px;display:block">{{ $message }}</span>@enderror
        </div>
        <div class="field"><label>{{ __('New password') }}</label><div style="position:relative"><input class="ut-input" type="password" name="password" placeholder="{{ __('New password') }}" style="padding-right:64px" required><button type="button" data-toggle-pw style="position:absolute;right:12px;top:11px;border:0;background:none;color:var(--text-2);font-family:var(--font-head);font-weight:600;font-size:12.5px">{{ __('Show') }}</button></div>
            @error('password')<span style="color:var(--accent);font-size:12.5px;margin-top:6px;display:block">{{ $message }}</span>@enderror
        </div>
        <div class="field"><label>{{ __('Confirm new password') }}</label><div style="position:relative"><input class="ut-input" type="password" name="password_confirmation" placeholder="{{ __('Re-enter new password') }}" style="padding-right:64px" required><button type="button" data-toggle-pw style="position:absolute;right:12px;top:11px;border:0;background:none;color:var(--text-2);font-family:var(--font-head);font-weight:600;font-size:12.5px">{{ __('Show') }}</button></div></div>
        <button class="ut-btn ut-btn-ink" type="submit" style="align-self:flex-start">{{ __('Update password') }}</button>
    </form>
</div>
<div class="ut-card" style="padding:22px">
    <div class="ut-row" style="justify-content:space-between;gap:12px">
        <div class="ut-row" style="gap:14px">
            <span style="width:44px;height:44px;border-radius:12px;background:#dcfce7;color:#15803d;display:grid;place-items:center"><x-frontend.icon n="shield" :size="20" /></span>
            <div><div style="font-family:var(--font-head);font-weight:600">{{ __('Two-factor authentication') }}</div><div class="muted" style="font-size:13px">{{ __('Add an extra layer of security') }}</div></div>
        </div>
        <button type="button" class="ut-btn ut-btn-ghost ut-btn-sm" onclick="utToast('{{ __('2FA setup started') }}')">{{ __('Enable') }}</button>
    </div>
</div>
@endsection

