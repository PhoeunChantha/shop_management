@extends('frontend.account.partials.shell', ['active' => 'wallet'])
@section('title', __('My Wallet').' — T-Shirt Shop')

@section('account')
<div class="ut-row" style="justify-content:space-between;align-items:flex-end;margin-bottom:18px;gap:12px;flex-wrap:wrap">
    <div><h2 style="font-size:24px">{{ __('My wallet') }}</h2><p class="muted" style="font-size:14px;margin-top:4px">{{ __('Store credit you can spend at checkout') }}</p></div>
</div>

{{-- balance + top-up --}}
<div class="ut-card" style="padding:26px;margin-bottom:20px">
    <div class="ut-row" style="justify-content:space-between;gap:20px;flex-wrap:wrap;align-items:flex-end">
        <div>
            <div class="muted" style="font-size:13px">{{ __('Available balance') }}</div>
            <div style="font-family:var(--font-head);font-weight:800;font-size:38px;line-height:1.1">{{ money($balance) }}</div>
        </div>
        <form method="POST" action="{{ route('frontend.account.wallet.topup') }}" class="ut-row" style="gap:10px;align-items:flex-end;flex-wrap:wrap">
            @csrf
            <div class="field"><label>{{ __('Top up amount ($)') }}</label><input class="ut-input" type="number" name="amount" min="1" step="0.01" value="{{ old('amount') }}" placeholder="20.00" required style="width:160px"></div>
            <button class="ut-btn ut-btn-ink ut-btn-lg" type="submit">{{ __('Top up via ABA PayWay') }}</button>
        </form>
    </div>
    @error('amount')<p style="color:var(--accent);font-size:12.5px;margin-top:10px">{{ $message }}</p>@enderror
</div>

{{-- transaction history --}}
<h3 style="font-size:17px;margin-bottom:12px">{{ __('Activity') }}</h3>
<div class="ut-card" style="padding:6px">
    @forelse($transactions as $t)
        <div class="ut-row" style="justify-content:space-between;gap:14px;padding:14px 16px;{{ !$loop->last ? 'border-bottom:1px solid var(--border-2)' : '' }}">
            <div>
                <div style="font-family:var(--font-head);font-weight:600;font-size:14.5px">{{ $t->description ?: ucfirst($t->type) }}</div>
                <div class="muted" style="font-size:12.5px">{{ $t->created_at?->format('M j, Y · g:i A') }} · {{ ucfirst($t->type) }}</div>
            </div>
            <div style="text-align:right">
                <div style="font-family:var(--font-head);font-weight:700;color:{{ (float) $t->amount < 0 ? 'var(--accent)' : '#15803d' }}">{{ (float) $t->amount < 0 ? '−' : '+' }}{{ money(abs((float) $t->amount)) }}</div>
                <div class="muted" style="font-size:12px">{{ __('Balance') }} {{ money($t->balance_after) }}</div>
            </div>
        </div>
    @empty
        <div style="padding:48px;text-align:center">
            <div style="width:60px;height:60px;border-radius:18px;background:var(--bg);display:grid;place-items:center;margin:0 auto 14px;color:var(--text-3)"><x-frontend.icon n="spark" :size="26" /></div>
            <h3>{{ __('No wallet activity yet') }}</h3><p class="muted" style="margin-top:6px">{{ __('Top up your wallet to pay faster at checkout.') }}</p>
        </div>
    @endforelse
</div>
@endsection
