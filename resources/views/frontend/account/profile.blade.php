@extends('frontend.account.partials.shell', ['active' => 'profile'])
@section('title', __('Profile').' — T-Shirt Shop')

@section('account')
<h2 style="font-size:24px;margin-bottom:4px">{{ __('Profile') }}</h2>
<p class="muted" style="font-size:14px;margin-bottom:18px">{{ __('Manage your personal information') }}</p>
<div class="ut-card" style="padding:26px">
    <div class="ut-row" style="gap:16px;margin-bottom:24px;padding-bottom:22px;border-bottom:1px solid var(--border)">
        <span style="width:72px;height:72px;border-radius:50%;background:var(--ink);color:#fff;display:grid;place-items:center;font-family:var(--font-head);font-weight:700;font-size:28px">{{ $user['first'][0] }}</span>
        <div><button type="button" class="ut-btn ut-btn-ghost ut-btn-sm">{{ __('Change photo') }}</button><p class="muted" style="font-size:12.5px;margin-top:8px">{{ __('JPG or PNG, max 2MB') }}</p></div>
    </div>
    <form class="ut-col" style="gap:16px;max-width:540px" method="POST" action="{{ route('frontend.account.profile.update') }}">
        @csrf
        @method('PUT')
        <div class="ut-form-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="field"><label>{{ __('First name') }}</label><input class="ut-input" name="first_name" value="{{ old('first_name', $user['first']) }}" required>
                @error('first_name')<span style="color:var(--accent);font-size:12.5px;margin-top:6px;display:block">{{ $message }}</span>@enderror
            </div>
            <div class="field"><label>{{ __('Last name') }}</label><input class="ut-input" name="last_name" value="{{ old('last_name', $user['last']) }}"></div>
        </div>
        <div class="field"><label>{{ __('Email address') }}</label><input class="ut-input" type="email" value="{{ $user['email'] }}" disabled><small class="muted" style="font-size:12px;margin-top:6px;display:block">{{ __('Email can’t be changed here.') }}</small></div>
        <div class="field"><label>{{ __('Phone number') }}</label><input class="ut-input" name="phone" value="{{ old('phone', $user['phone']) }}" placeholder="+855 12 345 678">
            @error('phone')<span style="color:var(--accent);font-size:12.5px;margin-top:6px;display:block">{{ $message }}</span>@enderror
        </div>
        <div class="ut-row" style="gap:10px;margin-top:4px"><button class="ut-btn ut-btn-ink" type="submit">{{ __('Save changes') }}</button><a class="ut-btn ut-btn-ghost" href="{{ route('frontend.account.dashboard') }}">{{ __('Cancel') }}</a></div>
    </form>
</div>
@endsection

