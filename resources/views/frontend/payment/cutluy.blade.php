@extends('frontend.layouts.frontend')
@section('title', $heading.' — T-Shirt Shop')

@section('content')
<section class="ut-wrap" style="padding:48px 0 72px">
    <div class="ut-qrpay" data-qrpay
        data-status-url="{{ $statusUrl }}"
        data-done-url="{{ $doneUrl }}"
        data-status="{{ $payment->status }}"
        data-expires="{{ $payment->expires_at?->getTimestamp() }}"
        data-qr="{{ $payment->qr_string }}">

        <p class="ut-qrpay__kicker">{{ $reference }}</p>
        <h1 class="ut-qrpay__title">{{ $heading }}</h1>
        <p class="ut-qrpay__amount">{{ money($payment->amount) }} <small>{{ $payment->currency }}</small></p>

        {{-- waiting: the QR --}}
        <div data-qrpay-open>
            <div class="ut-qrpay__frame">
                <div class="ut-qrpay__code" data-qrpay-code role="img" aria-label="{{ __('Payment QR code') }}"></div>
                <span class="ut-qrpay__corner ut-qrpay__corner--tl"></span><span class="ut-qrpay__corner ut-qrpay__corner--tr"></span>
                <span class="ut-qrpay__corner ut-qrpay__corner--bl"></span><span class="ut-qrpay__corner ut-qrpay__corner--br"></span>
            </div>

            <p class="ut-qrpay__state" data-qrpay-state aria-live="polite">
                <span class="ut-qrpay__dot"></span>
                <span data-qrpay-text>{{ __('Waiting for your payment…') }}</span>
            </p>
            <p class="ut-qrpay__timer" data-qrpay-timer hidden>{{ __('QR code expires in') }} <b data-qrpay-clock>5:00</b></p>

            <ol class="ut-qrpay__steps">
                <li>{{ __('Open your banking app (ABA, ACLEDA, Wing, Bakong…).') }}</li>
                <li>{{ __('Choose Scan QR and scan the code above.') }}</li>
                <li>{{ __('Confirm the payment. This page updates by itself — keep it open.') }}</li>
            </ol>

            @if ($payment->checkout_url)
                <a href="{{ $payment->checkout_url }}" class="ut-link ut-qrpay__alt" target="_blank" rel="noopener">{{ __('On this phone? Open the payment page') }}</a>
            @endif
        </div>

        {{-- paid --}}
        <div data-qrpay-paid hidden>
            <div class="ut-qrpay__result ut-qrpay__result--ok"><x-frontend.icon n="check" :size="34" /></div>
            <p class="ut-qrpay__state">{{ __('Payment received. Thank you!') }}</p>
            <a href="{{ $doneUrl }}" class="ut-btn ut-btn-ink ut-btn-lg">{{ __('Continue') }}</a>
        </div>

        {{-- expired / failed --}}
        <div data-qrpay-dead hidden>
            <div class="ut-qrpay__result ut-qrpay__result--bad">!</div>
            <p class="ut-qrpay__state" data-qrpay-dead-text>{{ __('This QR code has expired.') }}</p>
            <form method="POST" action="{{ $renewUrl }}">
                @csrf
                <button type="submit" class="ut-btn ut-btn-ink ut-btn-lg">{{ __('Get a new QR code') }}</button>
            </form>
        </div>

        <a href="{{ $backUrl }}" class="ut-qrpay__back">{{ $backLabel }}</a>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('assets/frontend/js/vendor/qrcode.min.js') }}"></script>
<script>
(function () {
    var box = document.querySelector('[data-qrpay]');
    if (!box) return;

    var TEXT = {
        pending: @js(__('Waiting for your payment…')),
        scanned: @js(__('QR scanned — confirm the payment in your banking app.')),
        expired: @js(__('This QR code has expired.')),
        failed: @js(__('The payment did not go through.')),
    };

    // Draw the QR from the string CutLuy returned (no image is fetched).
    var holder = box.querySelector('[data-qrpay-code]');
    try {
        var qr = qrcode(0, 'M');
        qr.addData(box.dataset.qr || '');
        qr.make();
        holder.innerHTML = qr.createSvgTag({ cellSize: 6, margin: 0, scalable: true });
    } catch (e) {
        holder.textContent = @js(__('Could not draw the QR code. Use the payment page link below.'));
    }

    var timer = null;
    function show(status) {
        var open = status === 'pending' || status === 'scanned';
        box.querySelector('[data-qrpay-open]').hidden = !open;
        box.querySelector('[data-qrpay-paid]').hidden = status !== 'paid';
        box.querySelector('[data-qrpay-dead]').hidden = open || status === 'paid';
        box.classList.toggle('is-scanned', status === 'scanned');
        if (open) box.querySelector('[data-qrpay-text]').textContent = TEXT[status];
        if (status === 'expired' || status === 'failed') box.querySelector('[data-qrpay-dead-text]').textContent = TEXT[status];
        if (!open && timer) { clearInterval(timer); timer = null; }
        if (status === 'paid') setTimeout(function () { window.location.href = box.dataset.doneUrl; }, 1600);
    }

    function check() {
        fetch(box.dataset.statusUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { if (d && d.status && d.status !== 'none') show(d.status); })
            .catch(function () {});
    }

    // Countdown to CutLuy's expiry; at zero ask the server, which offers a new QR.
    var expires = parseInt(box.dataset.expires, 10) || 0;
    var clock = null;
    function tick() {
        var left = Math.max(0, expires - Math.floor(Date.now() / 1000));
        box.querySelector('[data-qrpay-clock]').textContent = Math.floor(left / 60) + ':' + ('0' + (left % 60)).slice(-2);
        box.querySelector('[data-qrpay-timer]').classList.toggle('is-low', left <= 60);
        if (left === 0) { clearInterval(clock); check(); }
    }

    show(box.dataset.status);
    if (box.dataset.status === 'pending' || box.dataset.status === 'scanned') {
        if (expires) { box.querySelector('[data-qrpay-timer]').hidden = false; tick(); clock = setInterval(tick, 1000); }
        timer = setInterval(check, 4000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden && timer) check(); });
    }
})();
</script>
@endpush
