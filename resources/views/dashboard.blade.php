<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Overview</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Dashboard') }}</h2>
        </div>
    </x-slot>

    @php
        $admin = Auth::user();
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        // Compact payload consumed by ApexCharts (rendered client-side).
        $dashData = [
            'kpis' => collect($kpis)->map(fn ($k) => ['color' => $k['color'], 'series' => $k['series']])->all(),
            'revenue' => ['labels' => $chart['labels'], 'values' => $chart['values']],
            'status' => [
                'labels' => collect($statusBreakdown)->pluck('label')->all(),
                'values' => collect($statusBreakdown)->pluck('count')->all(),
                'colors' => collect($statusBreakdown)->pluck('color')->all(),
            ],
            'payment' => [
                'labels' => collect($paymentBreakdown)->pluck('label')->all(),
                'values' => collect($paymentBreakdown)->pluck('count')->all(),
                'colors' => collect($paymentBreakdown)->pluck('color')->all(),
            ],
        ];
    @endphp

    <div class="dash" x-data="{ shown: false }" x-init="$nextTick(() => shown = true)"
        :class="shown ? 'dash--in' : ''">

        {{-- ============ Control bar ============ --}}
        <div class="dash-bar">
            <div>
                <h1 class="dash-greeting">{{ $greeting }}, {{ $admin->name }}</h1>
                <p class="dash-date">{{ now()->format('l, F j, Y') }}</p>
            </div>
            <div class="dash-bar__actions">
                <div class="dash-segment" role="tablist" aria-label="Period">
                    @foreach ($ranges as $key => $label)
                        <a href="{{ route('admin.dashboard', ['range' => $key]) }}"
                            class="dash-segment__item {{ $range === $key ? 'is-active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <a href="{{ route('admin.products.create') }}" class="premium-button premium-button--dark">
                    <i class="fa-solid fa-plus"></i><span>New product</span>
                </a>
            </div>
        </div>

        {{-- ============ KPI cards ============ --}}
        <div class="dash-kpis">
            @foreach ($kpis as $kpi)
                <div class="dash-kpi" style="--acc: {{ $kpi['color'] }};">
                    <div class="dash-kpi__top">
                        <span class="dash-kpi__icon"><i class="fa-solid {{ $kpi['icon'] }}"></i></span>
                        <span class="dash-trend {{ $kpi['up'] ? 'is-up' : 'is-down' }}">
                            <i class="fa-solid {{ $kpi['up'] ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                            {{ $kpi['trend'] }}
                        </span>
                    </div>
                    <div class="dash-kpi__value" data-count="{{ $kpi['raw'] }}" data-prefix="{{ $kpi['prefix'] }}">{{ $kpi['value'] }}</div>
                    <div class="dash-kpi__label">{{ $kpi['label'] }} <span>· {{ $kpi['sub'] }}</span></div>
                    <div class="dash-kpi__spark" id="kpiSpark{{ $loop->index }}"></div>
                </div>
            @endforeach
        </div>

        {{-- ============ Operations queue ============ --}}
        <section class="dash-ops-panel">
            <div class="dash-ops-panel__intro">
                <span class="dash-ops-panel__icon"><i class="fa-solid fa-command"></i></span>
                <div>
                    <h3>Today&apos;s control queue</h3>
                    <p>High-signal tasks from orders, support, inventory, reviews, and alerts.</p>
                </div>
            </div>
            <div class="dash-ops-grid">
                @foreach ($operations as $item)
                    <a href="{{ $item['url'] }}" class="dash-op dash-op--{{ $item['tone'] }}">
                        <span class="dash-op__icon"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                        <span class="dash-op__copy">
                            <strong>{{ number_format($item['value']) }}</strong>
                            <small>{{ $item['label'] }}</small>
                        </span>
                        <i class="fa-solid fa-arrow-right dash-op__arrow"></i>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- ============ Chart + status ============ --}}
        <div class="dash-grid dash-grid--chart">
            {{-- Revenue area chart --}}
            <section class="dash-panel">
                <div class="dash-panel__head">
                    <div>
                        <h3>Revenue</h3>
                        <p>Last {{ $rangeLabel }}</p>
                    </div>
                    <div class="dash-panel__metric">
                        <strong>{{ $chart['total'] }}</strong>
                        <span>total · peak {{ $chart['peak'] }}</span>
                    </div>
                </div>
                <div id="revChart" class="dash-apex"></div>
            </section>

            {{-- Orders by status --}}
            <section class="dash-panel dash-panel--compact">
                <div class="dash-panel__head">
                    <div>
                        <h3>Order status</h3>
                        <p>All time</p>
                    </div>
                </div>
                @if (count($statusBreakdown))
                    <div id="statusChart" class="dash-apex dash-apex--donut"></div>
                    <ul class="dash-legend">
                        @foreach ($statusBreakdown as $s)
                            <li>
                                <span class="dash-legend__dot" style="background: {{ $s['color'] }};"></span>
                                <span class="dash-legend__label">{{ $s['label'] }}</span>
                                <span class="dash-legend__count">{{ $s['count'] }}</span>
                                <span class="dash-legend__pct">{{ $s['pct'] }}%</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="dash-empty"><i class="fa-solid fa-receipt"></i><p>No orders yet.</p></div>
                @endif
            </section>

            {{-- Payment mix --}}
            <section class="dash-panel dash-panel--compact">
                <div class="dash-panel__head">
                    <div>
                        <h3>Payment mix</h3>
                        <p>All time</p>
                    </div>
                </div>
                @if (count($paymentBreakdown))
                    <div id="paymentChart" class="dash-apex dash-apex--donut"></div>
                    <ul class="dash-legend">
                        @foreach ($paymentBreakdown as $payment)
                            <li>
                                <span class="dash-legend__dot" style="background: {{ $payment['color'] }};"></span>
                                <span class="dash-legend__label">{{ $payment['label'] }}</span>
                                <span class="dash-legend__count">{{ $payment['count'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="dash-empty"><i class="fa-solid fa-credit-card"></i><p>No payments yet.</p></div>
                @endif
            </section>
        </div>

        {{-- ============ Recent orders + low stock ============ --}}
        <div class="dash-grid dash-grid--feeds">
            {{-- Recent orders --}}
            <section class="dash-panel">
                <div class="dash-panel__head">
                    <div>
                        <h3>Recent orders</h3>
                        <p>Latest activity</p>
                    </div>
                    <a href="{{ route('admin.orders.index') }}" class="dash-link">View all <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="dash-table-wrap">
                    <table class="dash-table">
                        <thead>
                            <tr><th>Order</th><th>Customer</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Date</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="dash-table__id">{{ $order->order_number }}</a>
                                    </td>
                                    <td>{{ $order->customer_name }}</td>
                                    <td><span class="status-chip {{ $order->status->badge() }}">{{ $order->status->label() }}</span></td>
                                    <td class="text-end dash-table__amt">${{ number_format($order->grand_total, 2) }}</td>
                                    <td class="text-end dash-table__date">{{ ($order->placed_at ?? $order->created_at)?->format('d M') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="dash-empty"><i class="fa-solid fa-receipt"></i><p>No orders yet.</p></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Top products --}}
            <section class="dash-panel">
                <div class="dash-panel__head">
                    <div>
                        <h3>Top products</h3>
                        <p>Best sellers in this range</p>
                    </div>
                    <a href="{{ route('admin.products.index') }}" class="dash-link">Catalog <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="dash-products">
                    @forelse ($topProducts as $product)
                        <div class="dash-product-row">
                            <div class="dash-product-row__rank">{{ $loop->iteration }}</div>
                            <div class="dash-product-row__body">
                                <div class="dash-product-row__top">
                                    <strong>{{ $product['name'] }}</strong>
                                    <span>{{ $product['revenue'] }}</span>
                                </div>
                                <div class="dash-product-row__meta">
                                    <span>{{ $product['sku'] }}</span>
                                    <span>{{ number_format($product['sold']) }} sold</span>
                                </div>
                                <div class="dash-product-row__bar"><span style="width: {{ $product['pct'] }}%;"></span></div>
                            </div>
                        </div>
                    @empty
                        <div class="dash-empty"><i class="fa-solid fa-box-open"></i><p>No product sales in this range.</p></div>
                    @endforelse
                </div>
            </section>

            {{-- Low stock --}}
            <section class="dash-panel">
                <div class="dash-panel__head">
                    <div>
                        <h3>Low stock</h3>
                        <p>Running out soon</p>
                    </div>
                    <span class="dash-panel__badge"><i class="fa-solid fa-triangle-exclamation"></i></span>
                </div>
                <div class="dash-lowstock">
                    @forelse ($lowStock as $item)
                        <div class="dash-lowstock__row">
                            <span class="dash-lowstock__icon"><i class="fa-solid fa-shirt"></i></span>
                            <div class="dash-lowstock__body">
                                <p class="dash-lowstock__name">{{ $item['name'] }}</p>
                                <p class="dash-lowstock__sku">{{ $item['sku'] }}</p>
                                <div class="dash-lowstock__track"><span style="width: {{ $item['pct'] }}%;"></span></div>
                            </div>
                            <span class="dash-lowstock__qty">{{ $item['stock'] }}</span>
                        </div>
                    @empty
                        <div class="dash-empty dash-empty--ok">
                            <i class="fa-solid fa-circle-check"></i>
                            <p>All stock levels are healthy.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="dash-fulfillment">
            <div class="dash-fulfillment__meter" style="--pct: {{ $fulfillment['health'] }};">
                <span>{{ $fulfillment['health'] }}%</span>
            </div>
            <div class="dash-fulfillment__copy">
                <p class="section-kicker mb-1">Fulfillment pulse</p>
                <h3>Shipping workload is {{ $fulfillment['open'] > 0 ? 'active' : 'clear' }}</h3>
                <p>{{ number_format($fulfillment['open']) }} open orders, {{ number_format($fulfillment['shipped']) }} shipped, {{ number_format($fulfillment['delivered']) }} delivered in this range.</p>
            </div>
            <div class="dash-fulfillment__stats">
                <span><strong>{{ number_format($fulfillment['open']) }}</strong>Open</span>
                <span><strong>{{ number_format($fulfillment['shipped']) }}</strong>Shipped</span>
                <span><strong>{{ number_format($fulfillment['cancelled']) }}</strong>Cancelled</span>
            </div>
        </section>
    </div>

    <script type="application/json" id="dash-data">@json($dashData)</script>

    @push('js')
        <script>
            (function () {
                const data = JSON.parse(document.getElementById('dash-data').textContent);

                // Count-up animation on the KPI card values (runs on page load).
                document.querySelectorAll('.dash-kpi__value[data-count]').forEach((el) => {
                    const target = parseFloat(el.dataset.count) || 0;
                    const prefix = el.dataset.prefix || '';
                    const fmt = (n) => prefix + Math.round(n).toLocaleString();
                    if (target <= 0) { el.textContent = fmt(0); return; }
                    const duration = 1000;
                    const start = performance.now();
                    (function tick(now) {
                        const t = Math.min(1, (now - start) / duration);
                        const eased = 1 - Math.pow(1 - t, 3); // easeOutCubic
                        el.textContent = fmt(target * eased);
                        if (t < 1) requestAnimationFrame(tick);
                        else el.textContent = fmt(target);
                    })(start);
                });

                // Wait for the deferred ApexCharts CDN script to be ready.
                (function whenReady(tries) {
                    if (window.ApexCharts) { initDashboardCharts(data); }
                    else if (tries < 60) { setTimeout(() => whenReady(tries + 1), 100); }
                    else { console.error('ApexCharts failed to load from CDN.'); }
                })(0);

                function initDashboardCharts(data) {
                    const css = getComputedStyle(document.documentElement);
                    const dark = document.documentElement.classList.contains('dark');
                    const accent = (css.getPropertyValue('--admin-accent') || '#0f766e').trim();
                    const muted = dark ? '#94a3b8' : '#667085';
                    const grid = dark ? 'rgba(255,255,255,0.06)' : '#eef1f5';
                    const font = 'Manrope, ui-sans-serif, sans-serif';

                    // KPI sparklines
                    data.kpis.forEach((kpi, i) => {
                        const el = document.getElementById('kpiSpark' + i);
                        if (!el) return;
                        new ApexCharts(el, {
                            chart: { type: 'area', height: 42, sparkline: { enabled: true }, fontFamily: font },
                            series: [{ name: '', data: kpi.series }],
                            stroke: { width: 2, curve: 'smooth' },
                            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0 } },
                            colors: [kpi.color],
                            tooltip: { enabled: false },
                        }).render();
                    });

                    // Revenue area chart
                    const rev = document.getElementById('revChart');
                    if (rev) {
                        new ApexCharts(rev, {
                            chart: { type: 'area', height: 330, fontFamily: font, toolbar: { show: false }, zoom: { enabled: false },
                                animations: { enabled: true, easing: 'easeinout', speed: 800 } },
                            series: [{ name: 'Revenue', data: data.revenue.values }],
                            colors: [accent],
                            stroke: { width: 3, curve: 'smooth' },
                            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 92] } },
                            dataLabels: { enabled: false },
                            grid: { borderColor: grid, strokeDashArray: 4, xaxis: { lines: { show: false } }, padding: { left: 6, right: 6 } },
                            xaxis: { categories: data.revenue.labels, tickAmount: 6,
                                axisBorder: { show: false }, axisTicks: { show: false },
                                labels: { rotate: 0, hideOverlappingLabels: true, style: { colors: muted, fontSize: '11px' } } },
                            yaxis: { labels: { style: { colors: muted, fontSize: '11px' },
                                formatter: (v) => v >= 1000 ? '$' + (v / 1000).toFixed(1) + 'k' : '$' + Math.round(v) } },
                            tooltip: { theme: dark ? 'dark' : 'light', y: { formatter: (v) => '$' + Number(v).toLocaleString() } },
                        }).render();
                    }

                    // Orders-by-status donut
                    const donut = document.getElementById('statusChart');
                    if (donut && data.status.values.length) {
                        const total = data.status.values.reduce((a, b) => a + b, 0);
                        new ApexCharts(donut, {
                            chart: { type: 'donut', height: 210, fontFamily: font },
                            series: data.status.values,
                            labels: data.status.labels,
                            colors: data.status.colors,
                            stroke: { width: 2, colors: [dark ? '#121c31' : '#fff'] },
                            legend: { show: false },
                            dataLabels: { enabled: false },
                            plotOptions: { pie: { donut: { size: '72%', labels: { show: true,
                                name: { color: muted },
                                value: { color: dark ? '#e2e8f0' : '#101827', fontSize: '22px', fontWeight: 800 },
                                total: { show: true, label: 'Orders', color: muted, formatter: () => total } } } } },
                            tooltip: { theme: dark ? 'dark' : 'light' },
                        }).render();
                    }

                    // Payment-status donut
                    const payment = document.getElementById('paymentChart');
                    if (payment && data.payment.values.length) {
                        const total = data.payment.values.reduce((a, b) => a + b, 0);
                        new ApexCharts(payment, {
                            chart: { type: 'donut', height: 210, fontFamily: font },
                            series: data.payment.values,
                            labels: data.payment.labels,
                            colors: data.payment.colors,
                            stroke: { width: 2, colors: [dark ? '#121c31' : '#fff'] },
                            legend: { show: false },
                            dataLabels: { enabled: false },
                            plotOptions: { pie: { donut: { size: '72%', labels: { show: true,
                                name: { color: muted },
                                value: { color: dark ? '#e2e8f0' : '#101827', fontSize: '22px', fontWeight: 800 },
                                total: { show: true, label: 'Payments', color: muted, formatter: () => total } } } } },
                            tooltip: { theme: dark ? 'dark' : 'light' },
                        }).render();
                    }
                }
            })();
        </script>
    @endpush
</x-app-layout>
