{{-- CutLuy QR payment popup. Included by the wallet and order-confirmation
     pages when there is a QR to pay: $qr = payment, heading, reference,
     statusUrl, renewUrl, doneUrl, closeLabel. --}}
@php($payment = $qr['payment'])

<div class="ut-qrmodal" data-qrpay role="dialog" aria-modal="true" aria-labelledby="qrpayTitle"
    data-status-url="{{ $qr['statusUrl'] }}"
    data-done-url="{{ $qr['doneUrl'] }}"
    data-status="{{ $payment->status }}"
    data-expires="{{ $payment->expires_at?->getTimestamp() }}"
    data-qr="{{ $payment->qr_string }}">
    <div class="ut-qrmodal__backdrop" data-qrpay-close></div>

    <div class="ut-qrpay">
        <button type="button" class="ut-qrpay__close" data-qrpay-close aria-label="{{ __('Close') }}">&times;</button>

        <h2 class="ut-qrpay__title" id="qrpayTitle">{{ $qr['heading'] }}</h2>
        <p class="ut-qrpay__kicker">{{ $qr['reference'] }}</p>

        {{-- waiting: the QR --}}
        <div data-qrpay-open>
            {{-- Laid out like the standard KHQR card customers know from their bank:
                 red header, merchant name, amount, dashed rule, QR with the currency mark. --}}
            <div class="ut-khqr">
                <div class="ut-khqr__head"><span class="ut-khqr__mark">KHQR</span></div>
                <div class="ut-khqr__info">
                    <div class="ut-khqr__name">{{ $payment->merchantName() ?? app(\App\Services\Admin\SettingService::class)->siteName() }}</div>
                    <div class="ut-khqr__amount">{{ number_format((float) $payment->amount, 2) }} <small>{{ $payment->currency }}</small></div>
                </div>
                <div class="ut-khqr__qr">
                    <div class="ut-qrpay__code" data-qrpay-code role="img" aria-label="{{ __('Payment QR code') }}"></div>
                    <span class="ut-khqr__currency" aria-hidden="true">{{ $payment->currency === 'KHR' ? '៛' : '$' }}</span>
                </div>
            </div>

            <p class="ut-qrpay__state" aria-live="polite">
                <span class="ut-qrpay__dot"></span>
                <span data-qrpay-text>{{ __('Waiting for your payment…') }}</span>
            </p>
            <p class="ut-qrpay__timer" data-qrpay-timer hidden>{{ __('QR code expires in') }} <b data-qrpay-clock>5:00</b></p>

            {{-- A phone cannot scan its own screen: phones get a button that opens
                 ABA Mobile directly. Same tab, no target=_blank — Safari / Firefox
                 only open an app link from a direct tap. This popup keeps polling,
                 so coming back from ABA shows the result. --}}
            @if ($payment->abaDeeplink())
                <div class="ut-qrpay__alt">
                    <a href="{{ $payment->abaDeeplink() }}" class="ut-btn ut-btn-ink">{{ __('Pay with ABA Mobile') }}</a>
                    @if ($payment->checkout_url)
                        <a href="{{ $payment->checkout_url }}" class="ut-qrpay__alt-link">{{ __('Other bank app? Open the payment page') }}</a>
                    @endif
                </div>
            @endif
        </div>

        {{-- paid --}}
        <div data-qrpay-paid hidden>
            <div class="ut-qrpay__result ut-qrpay__result--ok"><x-frontend.icon n="check" :size="34" /></div>
            <p class="ut-qrpay__state">{{ __('Payment received. Thank you!') }}</p>
            <a href="{{ $qr['doneUrl'] }}" class="ut-btn ut-btn-ink ut-btn-lg">{{ __('Continue') }}</a>
        </div>

        {{-- expired / failed --}}
        <div data-qrpay-dead hidden>
            <div class="ut-qrpay__result ut-qrpay__result--bad">!</div>
            <p class="ut-qrpay__state" data-qrpay-dead-text>{{ __('This QR code has expired.') }}</p>
            <form method="POST" action="{{ $qr['renewUrl'] }}">
                @csrf
                <button type="submit" class="ut-btn ut-btn-ink ut-btn-lg">{{ __('Get a new QR code') }}</button>
            </form>
        </div>

        <button type="button" class="ut-qrpay__back" data-qrpay-close>{{ $qr['closeLabel'] }}</button>
    </div>
</div>

@push('scripts')
<script src="{{ asset('assets/frontend/js/vendor/qrcode.min.js') }}"></script>
<script>
(function () {
    var box = document.querySelector('[data-qrpay]');
    if (!box) return;

    // Page sections animate with transforms, which would trap a fixed overlay
    // inside them: lift the popup to <body> so it covers the whole window.
    document.body.appendChild(box);

    var TEXT = {
        pending: @js(__('Waiting for your payment…')),
        scanned: @js(__('QR scanned — confirm the payment in your banking app.')),
        expired: @js(__('This QR code has expired.')),
        failed: @js(__('The payment did not go through.')),
    };

    // Draw the QR from the string CutLuy returned (no image is fetched).
    var holder = box.querySelector('[data-qrpay-code]');
    try {
        var qr = qrcode(0, 'Q');
        qr.addData(box.dataset.qr || '');
        qr.make();
        holder.innerHTML = qr.createSvgTag({ cellSize: 6, margin: 0, scalable: true });
    } catch (e) {
        holder.textContent = @js(__('Could not draw the QR code. Use the payment page link below.'));
    }

    var timer = null, clock = null, paid = false;

    function stop() {
        if (timer) { clearInterval(timer); timer = null; }
        if (clock) { clearInterval(clock); clock = null; }
    }

    function show(status) {
        var open = status === 'pending' || status === 'scanned';
        paid = status === 'paid';
        box.querySelector('[data-qrpay-open]').hidden = !open;
        box.querySelector('[data-qrpay-paid]').hidden = !paid;
        box.querySelector('[data-qrpay-dead]').hidden = open || paid;
        box.classList.toggle('is-scanned', status === 'scanned');
        if (open) box.querySelector('[data-qrpay-text]').textContent = TEXT[status];
        if (status === 'expired' || status === 'failed') box.querySelector('[data-qrpay-dead-text]').textContent = TEXT[status];
        if (!open) stop();
        if (paid) setTimeout(function () { window.location.href = box.dataset.doneUrl; }, 1600);
    }

    function check() {
        fetch(box.dataset.statusUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { if (d && d.status && d.status !== 'none') show(d.status); })
            .catch(function () {});
    }

    // Countdown to CutLuy's expiry; at zero ask the server, which offers a new QR.
    var expires = parseInt(box.dataset.expires, 10) || 0;
    function tick() {
        var left = Math.max(0, expires - Math.floor(Date.now() / 1000));
        box.querySelector('[data-qrpay-clock]').textContent = Math.floor(left / 60) + ':' + ('0' + (left % 60)).slice(-2);
        box.querySelector('[data-qrpay-timer]').classList.toggle('is-low', left <= 60);
        if (left === 0) { clearInterval(clock); clock = null; check(); }
    }

    // Closing only hides the popup: the order / top-up stays unpaid and the
    // address drops ?pay so a reload does not reopen it.
    function close() {
        if (paid) { window.location.href = box.dataset.doneUrl; return; }
        stop();
        box.remove();
        document.documentElement.classList.remove('ut-qrmodal-open');
        try {
            var url = new URL(window.location.href);
            url.searchParams.delete('pay');
            window.history.replaceState({}, '', url);
        } catch (e) {}
    }
    box.querySelectorAll('[data-qrpay-close]').forEach(function (el) { el.addEventListener('click', close); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && document.body.contains(box)) close(); });

    document.documentElement.classList.add('ut-qrmodal-open');
    show(box.dataset.status);
    if (box.dataset.status === 'pending' || box.dataset.status === 'scanned') {
        if (expires) { box.querySelector('[data-qrpay-timer]').hidden = false; tick(); clock = setInterval(tick, 1000); }
        timer = setInterval(check, 4000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden && timer) check(); });
    }
})();
</script>
@endpush
