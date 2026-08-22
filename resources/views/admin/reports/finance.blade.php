@php
    $money = fn ($v) => '$'.number_format((float) $v, 2);

    // Compact period-over-period delta chip. `positiveGood` flips colour
    // semantics for metrics where a rise is bad (e.g. refunds).
    $delta = function (?array $c, bool $positiveGood = true) {
        if (! $c || $c['change'] === null) {
            return '<span class="kpi-delta is-flat"><i class="fa-solid fa-minus"></i>'.__('No prior data').'</span>';
        }
        $up = $c['direction'] === 'up';
        $good = $positiveGood ? $up : ! $up;
        $cls = $c['direction'] === 'flat' ? 'is-flat' : ($good ? 'is-up' : 'is-down');
        $icon = $up ? 'fa-arrow-trend-up' : ($c['direction'] === 'down' ? 'fa-arrow-trend-down' : 'fa-minus');
        return '<span class="kpi-delta '.$cls.'"><i class="fa-solid '.$icon.'"></i>'.number_format(abs($c['change']), 1).'%</span>';
    };

    $hasData = ($summary['orders'] ?? 0) > 0;
    $topProductMax = max(1, (float) ($topProducts->max('revenue') ?? 0));
    $paymentTotal = max(1, (int) $paymentMix->sum('count'));

    // Secondary report tiles — same targets and permissions as the sidebar.
    $reportLinks = [
        ['label' => __('Sales'), 'icon' => 'fa-chart-line', 'route' => 'admin.reports.sales', 'permission' => 'view sales reports', 'hint' => __('Trends, transactions, profit')],
        ['label' => __('Products'), 'icon' => 'fa-box-open', 'route' => 'admin.reports.products', 'permission' => 'view product reports', 'hint' => __('Best sellers and slow movers')],
        ['label' => __('Customers'), 'icon' => 'fa-user-group', 'route' => 'admin.reports.customers', 'permission' => 'view customer reports', 'hint' => __('Spend, cohorts, repeat rate')],
        ['label' => __('Payments'), 'icon' => 'fa-credit-card', 'route' => 'admin.reports.payments', 'permission' => 'view payment reports', 'hint' => __('Methods and settlement')],
        ['label' => __('Purchasing'), 'icon' => 'fa-clipboard-list', 'route' => 'admin.reports.purchasing', 'permission' => 'view purchasing reports', 'hint' => __('Supplier spend and POs')],
        ['label' => __('Inventory'), 'icon' => 'fa-warehouse', 'route' => 'admin.reports.stock', 'permission' => 'view stock reports', 'hint' => __('Stock levels and valuation')],
        ['label' => __('Returns'), 'icon' => 'fa-rotate-left', 'route' => 'admin.reports.returns', 'permission' => 'view return reports', 'hint' => __('Refunds and reasons')],
        ['label' => __('Registrations'), 'icon' => 'fa-user-plus', 'route' => 'admin.reports.register', 'permission' => 'view register reports', 'hint' => __('New customer signups')],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Analytics') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Reports Overview') }}</h2>
        </div>
    </x-slot>

    <x-admin.report-shell
        active="overview"
        :title="__('Reports Overview')"
        :filters="$filters"
        :action="route('admin.reports.index')"
        show-status
        show-payment
        :order-statuses="$orderStatuses"
        :payment-statuses="$paymentStatuses">

        <div class="fino" data-finance-overview>
            <p class="fino-period">
                <i class="fa-solid fa-circle-info"></i>
                {{ __('Showing') }} <strong>{{ \Carbon\Carbon::parse($filters['start_date'])->format('M d, Y') }} – {{ \Carbon\Carbon::parse($filters['end_date'])->format('M d, Y') }}</strong>
                <span class="fino-period__prev">{{ __('vs') }} {{ \Carbon\Carbon::parse($previousRange['start_date'])->format('M d') }} – {{ \Carbon\Carbon::parse($previousRange['end_date'])->format('M d, Y') }}</span>
            </p>

            @if (! $hasData)
                <div class="premium-card fino-empty">
                    <i class="fa-solid fa-chart-line"></i>
                    <h3>{{ __('No sales data for this period') }}</h3>
                    <p>{{ __('Try changing your date range or filters to see performance.') }}</p>
                    <a href="{{ route('admin.reports.index') }}" class="premium-button premium-button--dark">
                        <i class="fa-solid fa-sliders"></i>{{ __('Adjust filters') }}
                    </a>
                </div>
            @else

            {{-- ===================== HERO KPIs (4 only) ===================== --}}
            <div class="kpi-grid">
                <article class="kpi-card kpi-card--primary">
                    <header><span>{{ __('Net revenue') }}</span><i class="fa-solid fa-sack-dollar"></i></header>
                    <strong class="kpi-value">{{ $money($summary['net_revenue']) }}</strong>
                    <footer>{!! $delta($comparison['net_revenue'] ?? null) !!}<span class="kpi-vs">{{ __('vs prev.') }}</span></footer>
                    <div class="kpi-spark" data-spark="revenue"></div>
                </article>
                <article class="kpi-card">
                    <header><span>{{ __('Orders') }}</span><i class="fa-solid fa-bag-shopping"></i></header>
                    <strong class="kpi-value">{{ number_format($summary['orders']) }}</strong>
                    <footer>{!! $delta($comparison['orders'] ?? null) !!}<span class="kpi-vs">{{ __('vs prev.') }}</span></footer>
                    <div class="kpi-spark" data-spark="orders"></div>
                </article>
                <article class="kpi-card">
                    <header><span>{{ __('Average order') }}</span><i class="fa-solid fa-receipt"></i></header>
                    <strong class="kpi-value">{{ $money($summary['average_order']) }}</strong>
                    <footer>{!! $delta($comparison['average_order'] ?? null) !!}<span class="kpi-vs">{{ __('vs prev.') }}</span></footer>
                </article>
                <article class="kpi-card">
                    <header><span>{{ __('Gross profit') }}</span><i class="fa-solid fa-arrow-trend-up"></i></header>
                    <strong class="kpi-value">{{ $money($summary['gross_profit']) }}</strong>
                    <footer>{!! $delta($comparison['gross_profit'] ?? null) !!}<span class="kpi-badge">{{ number_format($summary['margin'], 1) }}% {{ __('margin') }}</span></footer>
                </article>
            </div>

            {{-- ===================== TREND CHART + PAYMENT MIX ===================== --}}
            <div class="fino-chart-grid">
                <section class="premium-card fino-chart">
                    <header class="fino-chart__head">
                        <div>
                            <h3>{{ __('Revenue trend') }}</h3>
                            <p>{{ __('Daily revenue vs the previous period') }}</p>
                        </div>
                        <a href="{{ route('admin.reports.export', ['type' => 'sales'] + request()->query()) }}" class="ghost-button">
                            <i class="fa-solid fa-download"></i><span>{{ __('Export') }}</span>
                        </a>
                    </header>
                    <div class="fino-chart__canvas" data-trend-chart></div>
                </section>

                <section class="premium-card fino-donut-card">
                    <h3>{{ __('Payment Mix') }}</h3>
                    <div class="fino-donut" data-paymix-chart></div>
                    <ul class="fino-donut-legend">
                        @foreach ($paymentMix as $payment)
                            <li>
                                <span class="fino-donut-legend__dot" data-pm-dot></span>
                                <span class="fino-donut-legend__name">{{ $payment['payment_status'] }}</span>
                                <span class="fino-donut-legend__pct">{{ number_format(($payment['count'] / $paymentTotal) * 100) }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>

            {{-- ===================== P&L LEDGER + TOP PRODUCTS ===================== --}}
            <div class="fino-detail-grid">
                <section class="premium-card fino-ledger">
                    <h3>{{ __('Profit & loss') }}</h3>
                    <ul>
                        <li><span>{{ __('Gross sales') }}</span><b>{{ $money($summary['gross_sales']) }}</b></li>
                        <li class="is-minus"><span>{{ __('Discounts') }}</span><b>−{{ $money($summary['discount_total']) }}</b></li>
                        <li class="is-subtotal"><span>{{ __('Net sales') }}</span><b>{{ $money($summary['net_sales']) }}</b></li>
                        <li class="is-minus"><span>{{ __('Cost of goods') }}</span><b>−{{ $money($summary['cogs']) }}</b></li>
                        <li class="is-total"><span>{{ __('Gross profit') }}</span><b>{{ $money($summary['gross_profit']) }}</b></li>
                    </ul>
                    <ul class="fino-ledger__aside">
                        <li><span>{{ __('Tax collected') }}</span><b>{{ $money($summary['tax_total']) }}</b></li>
                        <li><span>{{ __('Shipping') }}</span><b>{{ $money($summary['shipping_total']) }}</b></li>
                        <li><span>{{ __('Refunds') }}</span><b>{{ $money($summary['refunds']) }}</b></li>
                        <li><span>{{ __('Return rate') }}</span><b>{{ number_format($summary['return_rate'], 1) }}%</b></li>
                    </ul>
                </section>

                <section class="premium-card fino-products">
                    <header class="fino-products__head">
                        <h3>{{ __('Top products') }}</h3>
                        @can('view product reports')
                            <a href="{{ route('admin.reports.products', request()->only('start_date', 'end_date')) }}">{{ __('Full report') }} <i class="fa-solid fa-arrow-right"></i></a>
                        @endcan
                    </header>
                    @forelse ($topProducts as $product)
                        <div class="fino-rank">
                            <div>
                                <strong>{{ $product['name'] }}</strong>
                                <small>{{ $product['sku'] ?: __('No SKU') }} · {{ number_format($product['quantity']) }} {{ __('sold') }}</small>
                                <span class="fino-rank__bar"><i style="width: {{ round(($product['revenue'] / $topProductMax) * 100) }}%"></i></span>
                            </div>
                            <b>{{ $money($product['revenue']) }}</b>
                        </div>
                    @empty
                        <p class="fino-empty-hint">{{ __('Paid order lines will appear here.') }}</p>
                    @endforelse
                </section>
            </div>
            @endif

            {{-- ===================== EXPLORE REPORTS ===================== --}}
            <section>
                <p class="section-kicker fino-explore-kicker">{{ __('Explore reports') }}</p>
                <div class="fino-explore">
                    @foreach ($reportLinks as $link)
                        @can($link['permission'])
                            <a href="{{ route($link['route']) }}" class="fino-explore__tile">
                                <i class="fa-solid {{ $link['icon'] }}"></i>
                                <span>
                                    <strong>{{ $link['label'] }}</strong>
                                    <small>{{ $link['hint'] }}</small>
                                </span>
                                <i class="fa-solid fa-arrow-right fino-explore__arrow"></i>
                            </a>
                        @endcan
                    @endforeach
                </div>
            </section>
        </div>
    </x-admin.report-shell>

    @push('js')
    <script>
        (function () {
            const root = document.querySelector('[data-finance-overview]');
            if (!root || typeof ApexCharts === 'undefined') return;

            const CHART = @json($chart);
            const PAYMIX = @json($paymentMix->values());
            const INK = '#0f172a', GRID = '#eef1f6', MUTED = '#64748b';
            const PALETTE = ['#0f172a', '#0ea5e9', '#10b981', '#f59e0b', '#8b5cf6', '#f43f5e', '#14b8a6'];
            const money = (v) => '$' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            const labels = (CHART.labels || []).map(d => {
                const dt = new Date(d + 'T00:00:00');
                return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });

            // Main trend: current period solid area + previous period dashed ghost.
            const trendEl = root.querySelector('[data-trend-chart]');
            if (trendEl && labels.length) {
                new ApexCharts(trendEl, {
                    chart: { type: 'area', height: 320, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false },
                        animations: { enabled: true, easing: 'easeinout', speed: 500 } },
                    series: [
                        { name: '{{ __('Revenue') }}', data: CHART.revenue || [] },
                        { name: '{{ __('Previous period') }}', data: CHART.prevRevenue || [] },
                    ],
                    colors: ['#0ea5e9', '#cbd5e1'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: [2.5, 1.8], dashArray: [0, 6] },
                    fill: { type: ['gradient', 'solid'], gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 90] }, opacity: [1, 0] },
                    grid: { borderColor: GRID, strokeDashArray: 4, padding: { left: 8, right: 8 } },
                    legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px', labels: { colors: MUTED } },
                    xaxis: { categories: labels, tickAmount: Math.min(12, labels.length), labels: { style: { colors: MUTED, fontSize: '11px' } },
                        axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: { labels: { style: { colors: MUTED, fontSize: '11px' }, formatter: (v) => money(v) } },
                    tooltip: { shared: true, y: { formatter: (v) => '$' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2 }) } },
                }).render();
            }

            // KPI sparklines (revenue + orders share the daily series).
            const SPARKS = { revenue: { data: CHART.revenue || [], color: '#38bdf8' }, orders: { data: CHART.orders || [], color: '#0f172a' } };
            root.querySelectorAll('[data-spark]').forEach(node => {
                const m = SPARKS[node.dataset.spark];
                if (!m || !m.data.length) return;
                new ApexCharts(node, {
                    chart: { type: 'area', height: 44, sparkline: { enabled: true }, animations: { enabled: false } },
                    series: [{ data: m.data }],
                    colors: [m.color],
                    stroke: { curve: 'smooth', width: 1.8 },
                    fill: { type: 'gradient', gradient: { opacityFrom: 0.25, opacityTo: 0 } },
                    tooltip: { enabled: false },
                }).render();
            });

            // Payment-status donut.
            const donutEl = root.querySelector('[data-paymix-chart]');
            if (donutEl && PAYMIX.length) {
                new ApexCharts(donutEl, {
                    chart: { type: 'donut', height: 200, fontFamily: 'inherit' },
                    series: PAYMIX.map(p => p.count),
                    labels: PAYMIX.map(p => p.payment_status),
                    colors: PALETTE,
                    legend: { show: false },
                    dataLabels: { enabled: false },
                    stroke: { width: 2, colors: ['#fff'] },
                    plotOptions: { pie: { donut: { size: '68%', labels: { show: true,
                        total: { show: true, label: '{{ __('Orders') }}', color: MUTED, fontSize: '11px',
                            formatter: () => PAYMIX.reduce((a, p) => a + p.count, 0).toLocaleString() },
                        value: { color: INK, fontSize: '18px', fontWeight: 700 } } } } },
                }).render();
                root.querySelectorAll('[data-pm-dot]').forEach((dot, i) => {
                    dot.style.background = PALETTE[i % PALETTE.length];
                });
            }
        })();
    </script>
    @endpush

    <style>
        .fino { display: flex; flex-direction: column; gap: 1.15rem; }

        .fino-period { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #64748b; margin: 0; }
        .fino-period i { color: #94a3b8; }
        .fino-period strong { color: #334155; }
        .fino-period__prev { color: #94a3b8; }

        /* KPI cards (same visual language as the Sales report) */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.9rem; }
        .kpi-card { position: relative; overflow: hidden; background: #fff; border: 1px solid #e9edf4; border-radius: 16px; padding: 1.05rem 1.1rem 0;
            box-shadow: 0 1px 2px rgba(16,24,40,.04); display: flex; flex-direction: column; min-height: 148px; }
        .kpi-card--primary { background: linear-gradient(160deg, #0f172a 0%, #1e293b 100%); border-color: #0f172a; }
        .kpi-card--primary header span, .kpi-card--primary .kpi-vs { color: #94a3b8; }
        .kpi-card--primary .kpi-value { color: #fff; }
        .kpi-card--primary header i { background: rgba(255,255,255,.12); color: #e2e8f0; }
        .kpi-card header { display: flex; justify-content: space-between; align-items: center; }
        .kpi-card header span { font-size: 0.76rem; font-weight: 600; color: #64748b; }
        .kpi-card header i { width: 30px; height: 30px; display: grid; place-items: center; border-radius: 9px; background: #f1f5f9; color: #475569; font-size: 0.8rem; }
        .kpi-value { font-size: 1.85rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0.55rem 0 0.4rem; }
        .kpi-card footer { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; padding-bottom: 1rem; }
        .kpi-card:has(.kpi-spark) footer { padding-bottom: 0; }
        .kpi-vs { font-size: 0.72rem; color: #94a3b8; }
        .kpi-delta { display: inline-flex; align-items: center; gap: 0.28rem; font-size: 0.76rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 999px; }
        .kpi-delta.is-up { color: #059669; background: #ecfdf5; }
        .kpi-delta.is-down { color: #e11d48; background: #fef2f2; }
        .kpi-delta.is-flat { color: #64748b; background: #f1f5f9; }
        .kpi-badge { font-size: 0.72rem; font-weight: 600; color: #475569; background: #eef2f7; padding: 0.15rem 0.45rem; border-radius: 999px; }
        .kpi-spark { margin: auto -1.1rem 0; opacity: .9; }

        /* Chart + donut row */
        .fino-chart-grid { display: grid; grid-template-columns: 2.1fr 1fr; gap: 0.9rem; align-items: stretch; }
        .fino-chart { padding: 1.15rem 1.25rem 0.6rem; }
        .fino-chart__head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
        .fino-chart__head h3 { font-size: 1.02rem; font-weight: 650; color: #0f172a; margin: 0; }
        .fino-chart__head p { font-size: 0.8rem; color: #94a3b8; margin: 0.2rem 0 0; }
        .fino-chart__canvas { margin-top: 0.4rem; }
        .fino-donut-card { padding: 1.1rem 1.2rem; }
        .fino-donut-card h3 { font-size: 0.95rem; font-weight: 650; color: #0f172a; margin: 0 0 0.9rem; }
        .fino-donut { display: flex; justify-content: center; margin-bottom: 0.5rem; }
        .fino-donut-legend { list-style: none; margin: 0; padding: 0; }
        .fino-donut-legend li { display: grid; grid-template-columns: 12px 1fr auto; align-items: center; column-gap: 0.5rem; padding: 0.4rem 0; font-size: 0.8rem; }
        .fino-donut-legend li + li { border-top: 1px solid #f1f5f9; }
        .fino-donut-legend__dot { width: 9px; height: 9px; border-radius: 3px; background: #cbd5e1; }
        .fino-donut-legend__name { font-weight: 600; color: #334155; }
        .fino-donut-legend__pct { font-weight: 650; color: #475569; }

        /* Ledger + top products row */
        .fino-detail-grid { display: grid; grid-template-columns: 1fr 1.3fr; gap: 0.9rem; }
        .fino-ledger { padding: 1.1rem 1.2rem; }
        .fino-ledger h3, .fino-products h3 { font-size: 0.95rem; font-weight: 650; color: #0f172a; margin: 0 0 0.9rem; }
        .fino-ledger ul { list-style: none; margin: 0; padding: 0; }
        .fino-ledger li { display: flex; justify-content: space-between; align-items: baseline; padding: 0.48rem 0; font-size: 0.85rem; }
        .fino-ledger li span { color: #64748b; }
        .fino-ledger li b { font-weight: 650; color: #1e293b; font-variant-numeric: tabular-nums; }
        .fino-ledger li.is-minus b { color: #e11d48; }
        .fino-ledger li.is-subtotal { border-top: 1px solid #eef1f6; }
        .fino-ledger li.is-subtotal b { color: #0f172a; }
        .fino-ledger li.is-total { border-top: 2px solid #0f172a; margin-top: 0.2rem; padding-top: 0.6rem; }
        .fino-ledger li.is-total span { color: #0f172a; font-weight: 650; }
        .fino-ledger li.is-total b { color: #059669; font-size: 1.05rem; }
        .fino-ledger__aside { margin-top: 0.9rem !important; padding-top: 0.5rem !important; border-top: 1px dashed #e2e8f0; }
        .fino-ledger__aside li { font-size: 0.78rem; padding: 0.35rem 0; }
        .fino-ledger__aside li b { color: #64748b; font-weight: 600; }

        .fino-products { padding: 1.1rem 1.2rem; }
        .fino-products__head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.4rem; }
        .fino-products__head a { font-size: 0.78rem; font-weight: 650; color: #0ea5e9; text-decoration: none; }
        .fino-products__head a:hover { text-decoration: underline; }
        .fino-products__head a i { font-size: 0.65rem; }
        .fino-rank { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; padding: 0.55rem 0; }
        .fino-rank + .fino-rank { border-top: 1px solid #f1f5f9; }
        .fino-rank > div { flex: 1; min-width: 0; }
        .fino-rank strong { display: block; font-size: 0.84rem; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fino-rank small { font-size: 0.72rem; color: #94a3b8; }
        .fino-rank__bar { display: block; height: 5px; background: #f1f5f9; border-radius: 999px; overflow: hidden; margin-top: 0.35rem; }
        .fino-rank__bar i { display: block; height: 100%; background: linear-gradient(90deg, #0ea5e9, #6366f1); border-radius: 999px; }
        .fino-rank b { font-size: 0.85rem; font-weight: 650; color: #0f172a; font-variant-numeric: tabular-nums; }
        .fino-empty-hint { font-size: 0.82rem; color: #94a3b8; padding: 0.8rem 0; margin: 0; }

        /* Explore tiles */
        .fino-explore-kicker { margin-bottom: 0.6rem; }
        .fino-explore { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; }
        .fino-explore__tile { display: flex; align-items: center; gap: 0.75rem; background: #fff; border: 1px solid #e9edf4; border-radius: 14px;
            padding: 0.85rem 1rem; text-decoration: none; transition: border-color .15s, box-shadow .15s, transform .15s; }
        .fino-explore__tile:hover { border-color: #bae6fd; box-shadow: 0 8px 24px -14px rgba(14,165,233,.45); transform: translateY(-1px); }
        .fino-explore__tile > i:first-child { width: 34px; height: 34px; display: grid; place-items: center; flex-shrink: 0;
            border-radius: 10px; background: #f1f5f9; color: #475569; font-size: 0.85rem; }
        .fino-explore__tile span { display: flex; flex-direction: column; min-width: 0; }
        .fino-explore__tile strong { font-size: 0.84rem; font-weight: 650; color: #0f172a; }
        .fino-explore__tile small { font-size: 0.7rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fino-explore__arrow { margin-left: auto; color: #cbd5e1; font-size: 0.72rem; }
        .fino-explore__tile:hover .fino-explore__arrow { color: #0ea5e9; }

        /* Empty state */
        .fino-empty { text-align: center; padding: 3rem 1.5rem; display: flex; flex-direction: column; align-items: center; gap: 0.6rem; }
        .fino-empty i { font-size: 2rem; color: #cbd5e1; }
        .fino-empty h3 { font-size: 1.05rem; font-weight: 650; color: #334155; margin: 0.3rem 0 0; }
        .fino-empty p { font-size: 0.85rem; color: #94a3b8; margin: 0 0 0.6rem; }

        @media (max-width: 1180px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .fino-chart-grid, .fino-detail-grid { grid-template-columns: 1fr; }
            .fino-explore { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 720px) {
            .kpi-grid { grid-template-columns: 1fr; }
            .fino-explore { grid-template-columns: 1fr; }
        }
    </style>
</x-app-layout>
