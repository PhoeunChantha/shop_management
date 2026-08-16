@extends('frontend.account.partials.shell', ['active' => 'wallet'])
@section('title', __('My Wallet').' — T-Shirt Shop')

@section('account')
<div class="ut-row" style="justify-content:space-between;align-items:flex-end;margin-bottom:18px;gap:12px;flex-wrap:wrap">
    <div><h2 style="font-size:24px">{{ __('My wallet') }}</h2><p class="muted" style="font-size:14px;margin-top:4px">{{ __('Store credit you can spend at checkout') }}</p></div>
</div>

{{-- balance + top-up --}}
<div class="ut-card" style="padding:26px;margin-bottom:20px">
    <div style="margin-bottom:20px">
        <div class="muted" style="font-size:13px">{{ __('Available balance') }}</div>
        <div style="font-family:var(--font-head);font-weight:800;font-size:38px;line-height:1.1">{{ money($balance) }}</div>
    </div>

    @if(count($topupMethods))
        <form method="POST" action="{{ route('frontend.account.wallet.topup') }}" id="topup-form">
            @csrf
            <div class="field" style="max-width:220px;margin-bottom:16px">
                <label>{{ __('Top up amount ($)') }}</label>
                <input class="ut-input" type="number" name="amount" min="1" step="0.01" value="{{ old('amount') }}" placeholder="20.00" required>
                @error('amount')<p style="color:var(--accent);font-size:12.5px;margin-top:8px">{{ $message }}</p>@enderror
            </div>

            <label style="font-size:13px;font-weight:600;display:block;margin-bottom:8px">{{ __('Payment method') }}</label>
            <div class="ut-row" style="gap:10px;flex-wrap:wrap;margin-bottom:14px">
                @foreach($topupMethods as $i => $m)
                    <label class="topup-method" data-type="{{ $m['type'] }}">
                        <input type="radio" name="payment_method" value="{{ $m['code'] }}" @checked((string) old('payment_method', $i === 0 ? $m['code'] : '') === $m['code']) onchange="topupSelect(this)" style="position:absolute;opacity:0;pointer-events:none">
                        <span class="topup-method__box" style="display:flex;align-items:center;gap:9px;padding:12px 16px;border-radius:var(--r-md);border:1.5px solid var(--border);background:#fff;font-family:var(--font-head);font-weight:600;font-size:13.5px;cursor:pointer;min-width:120px">
                            @if(!empty($m['image']))
                                <img src="{{ $m['image'] }}" alt="{{ $m['name'] }}" style="height:20px;max-width:44px;object-fit:contain">
                            @else
                                <x-frontend.icon :n="$m['type'] === 'manual' ? 'lock' : 'card'" :size="20" />
                            @endif
                            {{ $m['name'] }}
                        </span>
                    </label>
                @endforeach
            </div>

            @foreach($topupMethods as $i => $m)
                @if($m['type'] === 'manual')
                    <div data-topup-panel="{{ $m['code'] }}" style="display:none;background:var(--bg);border-radius:var(--r-md);padding:18px 20px;margin-bottom:14px">
                        @if($m['description'])<p class="muted" style="font-size:13.5px;margin:0 0 12px">{{ $m['description'] }}</p>@endif
                        @if($m['qr_image'])
                            <div style="text-align:center;margin-bottom:14px"><img src="{{ $m['qr_image'] }}" alt="{{ __('Payment QR') }}" style="width:180px;height:180px;object-fit:contain;border:1px solid var(--border);border-radius:12px;padding:8px;background:#fff"></div>
                        @endif
                        @if($m['bank_name'] || $m['account_name'] || $m['account_number'])
                            <div style="background:#fff;border-radius:var(--r-md);padding:12px 16px;border:1px solid var(--border)">
                                @if($m['bank_name'])<div class="ut-row" style="justify-content:space-between;font-size:13.5px;padding:4px 0"><span class="muted">{{ __('Bank') }}</span><b>{{ $m['bank_name'] }}</b></div>@endif
                                @if($m['account_name'])<div class="ut-row" style="justify-content:space-between;font-size:13.5px;padding:4px 0"><span class="muted">{{ __('Account name') }}</span><b>{{ $m['account_name'] }}</b></div>@endif
                                @if($m['account_number'])<div class="ut-row" style="justify-content:space-between;font-size:13.5px;padding:4px 0"><span class="muted">{{ __('Account no.') }}</span><b class="mono">{{ $m['account_number'] }}</b></div>@endif
                            </div>
                        @endif
                        @if($m['instructions'])<p class="muted" style="font-size:13px;line-height:1.65;margin:12px 0 0">{{ $m['instructions'] }}</p>@endif
                        <p style="font-size:12.5px;color:#b45309;background:rgba(245,158,11,.1);border-radius:10px;padding:9px 12px;margin:12px 0 0">
                            <b>{{ __('Note:') }}</b> {{ __('After you transfer, submit the request below. Your balance is credited once we confirm your payment.') }}
                        </p>
                    </div>
                @endif
            @endforeach

            <button class="ut-btn ut-btn-ink ut-btn-lg" type="submit" id="topup-submit">{{ __('Continue to payment') }}</button>
            @error('payment_method')<p style="color:var(--accent);font-size:12.5px;margin-top:10px">{{ $message }}</p>@enderror
        </form>
    @else
        <p class="muted" style="font-size:13.5px">{{ __('No payment methods are available right now. Please check back later.') }}</p>
    @endif
</div>

{{-- pending manual top-up requests --}}
@if($pendingTopups->isNotEmpty())
    <div class="ut-card" style="padding:18px 20px;margin-bottom:20px;border:1px dashed var(--border)">
        <h3 style="font-size:15px;margin-bottom:10px">{{ __('Pending top-up requests') }}</h3>
        @foreach($pendingTopups as $p)
            <div class="ut-row" style="justify-content:space-between;gap:10px;padding:8px 0;{{ !$loop->last ? 'border-bottom:1px solid var(--border-2)' : '' }}">
                <div>
                    <b style="font-family:var(--font-head)">{{ money($p->amount) }}</b>
                    <span class="muted" style="font-size:12.5px"> · {{ $p->tran_id }} · {{ $p->created_at?->format('M j, Y · g:i A') }}</span>
                </div>
                <span style="font-size:11.5px;font-weight:700;color:#b45309;background:rgba(245,158,11,.12);padding:4px 11px;border-radius:999px">{{ __('Awaiting confirmation') }}</span>
            </div>
        @endforeach
    </div>
@endif

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

<script>
    function topupSelect(input) {
        document.querySelectorAll('.topup-method__box').forEach(b => b.style.borderColor = 'var(--border)');
        const label = input.closest('.topup-method');
        label.querySelector('.topup-method__box').style.borderColor = 'var(--ink)';

        document.querySelectorAll('[data-topup-panel]').forEach(p => p.style.display = 'none');
        const panel = document.querySelector(`[data-topup-panel="${input.value}"]`);
        if (panel) panel.style.display = 'block';

        const btn = document.getElementById('topup-submit');
        if (btn) btn.textContent = label.dataset.type === 'manual'
            ? @json(__('Submit top-up request'))
            : @json(__('Continue to payment'));
    }

    document.addEventListener('DOMContentLoaded', function () {
        const checked = document.querySelector('.topup-method input:checked');
        if (checked) topupSelect(checked);
    });
</script>
@endsection
