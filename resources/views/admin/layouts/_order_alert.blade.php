{{-- New website order → popup and/or sound on every admin page.
     Switches live in Settings → Notifications; staff need "view orders". --}}
@php
    $orderAlert = auth()->check() && auth()->user()->can('view orders')
        ? app(\App\Services\Admin\SettingService::class)->orderAlert()
        : ['enabled' => false];
@endphp

@if ($orderAlert['enabled'])
    <div class="modal-backdrop-premium" x-data="orderAlertModal" x-show="open" x-cloak
        x-transition.opacity.duration.150ms @keydown.escape.window="close()" @click.self="close()" style="display:none;">
        <div class="form-modal order-alert" role="alertdialog" aria-modal="true" aria-labelledby="orderAlertTitle"
            x-show="open" x-transition:enter="fm-enter" x-transition:enter-start="fm-from" x-transition:enter-end="fm-to"
            x-transition:leave="fm-leave" x-transition:leave-start="fm-to" x-transition:leave-end="fm-from">
            <div class="order-alert__head">
                <div class="order-alert__bell"><i class="fa-solid fa-bell"></i></div>
                <p class="order-alert__kicker">{{ __('Website order') }}</p>
                <h3 id="orderAlertTitle" x-text="orders.length > 1 ? orders.length + ' ' + @js(__('new orders')) : @js(__('New order received'))"></h3>
            </div>

            <ul class="order-alert__list">
                <template x-for="o in orders" :key="o.id">
                    <li>
                        <a :href="o.url" class="order-alert__row">
                            <span class="order-alert__who">
                                <strong x-text="'#' + o.number"></strong>
                                <small><span x-text="o.customer"></span> · <span x-text="o.items"></span> {{ __('items') }} · <span x-text="o.placed"></span></small>
                            </span>
                            <span class="order-alert__sum">
                                <strong x-text="o.total"></strong>
                                <small x-text="o.payment"></small>
                            </span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </li>
                </template>
            </ul>

            <div class="form-modal__foot order-alert__foot">
                <button type="button" class="modal-cancel" @click="close()">{{ __('Dismiss') }}</button>
                <a class="form-submit-button" x-ref="view"
                    :href="orders.length === 1 ? orders[0].url : @js(route('admin.orders.index'))">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span x-text="orders.length === 1 ? @js(__('View order')) : @js(__('View all orders'))"></span>
                </a>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var CFG = {
                url: @js(route('admin.orders.alerts')),
                popup: @js($orderAlert['popup']),
                sound: @js($orderAlert['sound']),
                baseline: @js((int) \App\Models\Order::max('id')),
                every: 15000,
            };
            var KEY = 'admin-order-alert-last';

            // Last order id any admin tab has already handled. Shared through
            // localStorage so two open tabs do not both ring for one order, and
            // an order placed while moving between pages is not skipped.
            function seen() {
                try {
                    var s = JSON.parse(localStorage.getItem(KEY) || 'null');
                    if (s && Date.now() - s.at < 90000) return Math.max(0, parseInt(s.id, 10) || 0);
                } catch (e) {}
                return null;
            }
            function remember(id) {
                try { localStorage.setItem(KEY, JSON.stringify({ id: id, at: Date.now() })); } catch (e) {}
            }

            var last = seen();
            if (last === null) { last = CFG.baseline; remember(last); }

            /* ---- sound: three rising notes, played twice (synthesised, no audio file) ---- */
            var ctx = null;
            function audio() {
                if (ctx) return ctx;
                var C = window.AudioContext || window.webkitAudioContext;
                if (!C) return null;
                try { ctx = new C(); } catch (e) { ctx = null; }
                return ctx;
            }
            ['pointerdown', 'keydown', 'touchstart'].forEach(function (ev) {
                document.addEventListener(ev, function () {
                    var c = audio(); if (c && c.state === 'suspended') c.resume().catch(function () {});
                }, { once: true, passive: true });
            });
            function note(c, freq, at, dur) {
                var o = c.createOscillator(), g = c.createGain();
                o.type = 'sine'; o.frequency.setValueAtTime(freq, at);
                g.gain.setValueAtTime(0.0001, at);
                g.gain.exponentialRampToValueAtTime(0.3, at + 0.015);
                g.gain.exponentialRampToValueAtTime(0.0001, at + dur);
                o.connect(g); g.connect(c.destination); o.start(at); o.stop(at + dur + 0.05);
            }
            function ring() {
                var c = audio(); if (!c) return;
                var go = function () {
                    var t = c.currentTime + 0.02;
                    [0, 0.9].forEach(function (off) {
                        note(c, 783.99, t + off, 0.22);
                        note(c, 987.77, t + off + 0.16, 0.22);
                        note(c, 1318.5, t + off + 0.32, 0.5);
                    });
                };
                if (c.state === 'suspended') c.resume().then(go).catch(function () {}); else go();
            }

            function poll() {
                var shared = seen();
                if (shared !== null && shared > last) last = shared; // another tab already alerted
                return fetch(CFG.url + '?after=' + last, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        if (!data) return;
                        var orders = data.orders || [];
                        last = Math.max(last, parseInt(data.latest_id, 10) || 0);
                        remember(last);
                        if (!orders.length) return;
                        if (CFG.sound) ring();
                        if (CFG.popup) window.dispatchEvent(new CustomEvent('order-alert', { detail: { orders: orders } }));
                    })
                    .catch(function () {});
            }
            setInterval(poll, CFG.every);
            window.adminOrderAlert = { poll: poll, ring: ring };

            document.addEventListener('alpine:init', function () {
                window.Alpine.data('orderAlertModal', function () {
                    return {
                        open: false,
                        orders: [],
                        init() {
                            window.addEventListener('order-alert', (e) => {
                                // Still open from an earlier order: add to the list instead of replacing it.
                                var known = this.open ? this.orders : [];
                                var ids = known.map(function (o) { return o.id; });
                                this.orders = known.concat(e.detail.orders.filter(function (o) { return ids.indexOf(o.id) === -1; }));
                                this.open = true;
                                this.$nextTick(() => this.$refs.view && this.$refs.view.focus());
                            });
                        },
                        close() { this.open = false; },
                    };
                });
            });
        })();
    </script>
@endif
