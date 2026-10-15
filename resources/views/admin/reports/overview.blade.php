@php
    $money = fn ($v) => '$'.number_format((float) $v, 2);
    $hasData = $summary['orders'] > 0;
    $prev = $filters->previous();
    $buyers = $customerMix['new']['customers'] + $customerMix['returning']['customers'];
    $hints = [
        'admin.reports.sales' => __('Waterfall, products, customers'),
        'admin.reports.orders' => __('Status, backlog, shipping time'),
        'admin.reports.products' => __('Best sellers and slow movers'),
        'admin.reports.stock' => __('Stock levels and valuation'),
        'admin.reports.customers' => __('Buyers and registrations'),
        'admin.reports.payments' => __('Methods and settlement'),
        'admin.reports.purchasing' => __('Supplier spend and POs'),
        'admin.reports.returns' => __('Returns and refunds'),
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
        :filters="$filters->toQuery()"
        :action="route('admin.reports.index')"
        :export-url="route('admin.reports.export', $filters->toQuery())"
        :pdf-url="route('admin.reports.export', ['format' => 'pdf'] + $filters->toQuery())"
        show-status
        show-payment
        :order-statuses="$orderStatuses"
        :payment-statuses="$paymentStatuses">

        <div class="rpt" data-overview data-chart='@json($chart)'>
            <p class="rpt-period">
                <i class="fa-regular fa-calendar"></i>
                <strong>{{ $filters->start->format('M d, Y') }} – {{ $filters->end->format('M d, Y') }}</strong>
                <span class="rpt-period__prev">{{ __('compared with') }} {{ $prev->start->format('M d') }} – {{ $prev->end->format('M d, Y') }}</span>
            </p>

            @if (! $hasData)
                <div class="rpt-empty">
                    <i class="fa-solid fa-chart-line"></i>
                    <h3>{{ __('No paid sales in this period') }}</h3>
                    <p>
                        {{ __('Sales count once payment is captured.') }}
                        @if ($awaiting['orders'] > 0)
                            {{ trans_choice('{1} :count order is awaiting payment (:amount).|[2,*] :count orders are awaiting payment (:amount).', $awaiting['orders'], ['count' => $awaiting['orders'], 'amount' => $money($awaiting['amount'])]) }}
                        @endif
                    </p>
                </div>
            @else

            {{-- Headline KPIs --}}
            <div class="kpi-grid">
                <article class="kpi-card kpi-card--primary">
                    <header><span>{{ __('Total sales') }}</span><i class="fa-solid fa-sack-dollar"></i></header>
                    <strong class="kpi-value">{{ $money($summary['total_sales']) }}</strong>
                    <footer><x-admin.report-delta :data="$comparison['total_sales']" /><span class="kpi-vs">{{ __('incl. tax & shipping, after refunds') }}</span></footer>
                </article>
                <article class="kpi-card">
                    <header><span>{{ __('Net sales') }}</span><i class="fa-solid fa-scale-balanced"></i></header>
                    <strong class="kpi-value">{{ $money($summary['net_sales']) }}</strong>
                    <footer><x-admin.report-delta :data="$comparison['net_sales']" /><span class="kpi-vs">{{ __('after discounts & refunds') }}</span></footer>
                </article>
                <article class="kpi-card">
                    <header><span>{{ __('Paid orders') }}</span><i class="fa-solid fa-bag-shopping"></i></header>
                    <strong class="kpi-value">{{ number_format($summary['orders']) }}</strong>
                    <footer><x-admin.report-delta :data="$comparison['orders']" /><span class="kpi-vs">{{ number_format($summary['units']) }} {{ __('units') }}</span></footer>
                </article>
                <article class="kpi-card">
                    <header><span>{{ __('Average order value') }}</span><i class="fa-solid fa-receipt"></i></header>
                    <strong class="kpi-value">{{ $money($summary['average_order_value']) }}</strong>
                    <footer><x-admin.report-delta :data="$comparison['average_order_value']" /><span class="kpi-vs">{{ __('before tax & shipping') }}</span></footer>
                </article>
            </div>

            {{-- Supporting metrics --}}
            <div class="rpt-metrics">
                <div class="rpt-metric">
                    <span>{{ __('Refunds') }}</span>
                    <strong class="{{ $summary['refunds'] > 0 ? 'rpt-neg' : '' }}">{{ $money($summary['refunds']) }}</strong>
                    <small><x-admin.report-delta :data="$comparison['refunds']" inverse />{{ __('recorded') }} {{ $money($summary['recorded_refunds']) }} · {{ __('implied') }} {{ $money($summary['implied_refunds']) }}</small>
                </div>
                <div class="rpt-metric">
                    <span>{{ __('New customers') }}</span>
                    <strong>{{ number_format($summary['new_customers']) }}</strong>
                    <small><x-admin.report-delta :data="$comparison['new_customers']" />{{ __('first paid order') }}</small>
                </div>
                <div class="rpt-metric">
                    <span>{{ __('Awaiting payment') }}</span>
                    <strong>{{ $money($awaiting['amount']) }}</strong>
                    <small>{{ trans_choice('{0} no open orders|{1} :count unpaid order|[2,*] :count unpaid orders', $awaiting['orders'], ['count' => $awaiting['orders']]) }} · {{ __('not counted as sales') }}</small>
                </div>
                @if ($finance)
                    <div class="rpt-metric">
                        <span>{{ __('Gross profit') }}</span>
                        <strong>{{ $money($finance['gross_profit']) }}</strong>
                        <small>
                            <x-admin.report-delta :data="$comparison['gross_profit']" />
                            {{ $finance['margin'] !== null ? number_format($finance['margin'], 1).'% '.__('margin') : '' }}
                        </small>
                    </div>
                @endif
            </div>

            {{-- Trend + waterfall --}}
            <div class="rpt-grid">
                <section class="rpt-panel">
                    <header class="rpt-panel__head">
                        <div>
                            <h3>{{ __('Sales over time') }}</h3>
                            <p>{{ $unit === 'day' ? __('Total sales per day') : __('Total sales per month') }}</p>
                        </div>
                        <div class="rpt-legend">
                            <span><i></i>{{ __('This period') }}</span>
                            <span><i class="is-ghost"></i>{{ __('Previous period') }}</span>
                        </div>
                    </header>
                    <div data-trend-chart role="img" aria-label="{{ __('Total sales over time, this period versus previous period') }}"></div>
                </section>

                <section class="rpt-panel">
                    <header class="rpt-panel__head">
                        <div>
                            <h3>{{ __('Sales waterfall') }}</h3>
                            <p>{{ __('Paid orders placed in the period') }}</p>
                        </div>
                        @can('view sales reports')
                            <a href="{{ route('admin.reports.sales', $filters->toQuery()) }}">{{ __('Details') }} <i class="fa-solid fa-arrow-right"></i></a>
                        @endcan
                    </header>
                    <dl class="rpt-ledger">
                        <div><dt>{{ __('Gross sales') }}</dt><dd>{{ $money($summary['gross_sales']) }}</dd></div>
                        <div class="is-minus"><dt>{{ __('Discounts') }}</dt><dd>− {{ $money($summary['discounts']) }}</dd></div>
                        <div class="is-minus"><dt>{{ __('Product refunds') }}</dt><dd>− {{ $money($summary['merchandise_refunds']) }}</dd></div>
                        <div class="is-subtotal"><dt>{{ __('Net sales') }}</dt><dd>{{ $money($summary['net_sales']) }}</dd></div>
                        <div><dt>{{ __('Tax') }}</dt><dd>+ {{ $money($summary['tax']) }}</dd></div>
                        <div><dt>{{ __('Shipping') }}</dt><dd>+ {{ $money($summary['shipping']) }}</dd></div>
                        @if ($summary['tax_shipping_refunds'] > 0)
                            <div class="is-minus"><dt>{{ __('Tax & shipping refunded') }}</dt><dd>− {{ $money($summary['tax_shipping_refunds']) }}</dd></div>
                        @endif
                        <div class="is-total"><dt>{{ __('Total sales') }}</dt><dd>{{ $money($summary['total_sales']) }}</dd></div>
                        @if ($finance)
                            <div class="is-minus is-aside"><dt>{{ __('Cost of goods') }}<small>{{ $finance['uncosted_units'] > 0 ? trans_choice('{1} :count unit has no cost recorded|[2,*] :count units have no cost recorded', $finance['uncosted_units'], ['count' => $finance['uncosted_units']]) : __('from cost at time of sale') }}</small></dt><dd>− {{ $money($finance['cogs']) }}</dd></div>
                            <div class="is-aside"><dt>{{ __('Gross profit (net sales − cost)') }}</dt><dd>{{ $money($finance['gross_profit']) }}</dd></div>
                        @endif
                    </dl>
                </section>
            </div>

            {{-- Mix: two chart panels instead of four ranked lists --}}
            @php
                $productBars = collect($topProducts)->map(fn ($r) => ['label' => $r['name'], 'value' => round((float) $r['net_sales'], 2), 'meta' => (int) $r['quantity']])->values();
                $categoryBars = collect($topCategories)->map(fn ($r) => ['label' => $r['name'], 'value' => round((float) $r['net_sales'], 2), 'meta' => (int) $r['quantity']])->values();
                $paymentBars = collect($paymentMix)->map(fn ($r) => ['label' => $r['method'], 'value' => round((float) $r['total_sales'], 2), 'meta' => (int) $r['orders']])->values();
            @endphp
            <div class="rpt-grid">
                <section class="rpt-panel" data-mix-panel>
                    <header class="rpt-panel__head">
                        <div><h3>{{ __('Top sellers') }}</h3><p>{{ __('By net sales') }}</p></div>
                        <div class="rpt-toggle" role="tablist" aria-label="{{ __('Top sellers by') }}">
                            <button type="button" role="tab" class="is-active" aria-selected="true" data-mix-tab="products"
                                data-href="{{ route('admin.reports.sales', ['view' => 'products'] + $filters->toQuery()) }}">{{ __('Products') }}</button>
                            <button type="button" role="tab" aria-selected="false" data-mix-tab="categories"
                                data-href="{{ route('admin.reports.sales', ['view' => 'categories'] + $filters->toQuery()) }}">{{ __('Categories') }}</button>
                        </div>
                    </header>
                    <div class="rpt-bars" data-bar-chart data-unit="{{ __('units') }}"
                        data-products='@json($productBars)' data-categories='@json($categoryBars)'></div>
                    @can('view sales reports')
                        <a class="rpt-panel__more" data-mix-more href="{{ route('admin.reports.sales', ['view' => 'products'] + $filters->toQuery()) }}">
                            {{ __('View full breakdown') }} <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    @endcan
                </section>

                <section class="rpt-panel">
                    <header class="rpt-panel__head">
                        <div><h3>{{ __('Payment methods') }}</h3><p>{{ __('Total sales by method') }}</p></div>
                    </header>
                    <div class="rpt-bars" data-bar-chart data-unit="{{ __('orders') }}" data-payments='@json($paymentBars)'></div>

                    <div class="rpt-subsection">
                        <h4>{{ __('New vs returning') }} <small>{{ __('Buyers in the period') }}</small></h4>
                        <div class="rpt-split" role="img" aria-label="{{ __('New customers') }} {{ $customerMix['new']['customers'] }}, {{ __('Returning') }} {{ $customerMix['returning']['customers'] }}">
                            <i style="width: {{ $buyers ? $customerMix['new']['customers'] / $buyers * 100 : 0 }}%"></i>
                            <i style="width: {{ $buyers ? $customerMix['returning']['customers'] / $buyers * 100 : 0 }}%"></i>
                        </div>
                        <div class="rpt-split-legend">
                            <div><span>{{ __('New') }}</span><strong>{{ number_format($customerMix['new']['customers']) }}</strong>{{ $money($customerMix['new']['total_sales']) }}</div>
                            <div><span>{{ __('Returning') }}</span><strong>{{ number_format($customerMix['returning']['customers']) }}</strong>{{ $money($customerMix['returning']['total_sales']) }}</div>
                        </div>
                    </div>
                </section>
            </div>
            @endif

            {{-- Explore --}}
            <div class="rpt-explore">
                @foreach (\App\Services\Admin\Reports\ReportNavigation::domains() as $domain)
                    @continue($domain['route'] === 'admin.reports.index')
                    @can($domain['permission'])
                        <a href="{{ route($domain['route']) }}">
                            <i class="fa-solid {{ $domain['icon'] }}"></i>
                            <span><strong>{{ $domain['label'] }}</strong><small>{{ $hints[$domain['route']] ?? '' }}</small></span>
                        </a>
                    @endcan
                @endforeach
            </div>
        </div>
    </x-admin.report-shell>

    @push('js')
    <script>
        (function () {
            let charts = [];
            const destroy = () => { charts.forEach((c) => { try { c.destroy(); } catch (e) {} }); charts = []; };

            const css = () => getComputedStyle(document.documentElement);
            const v = (name, fallback) => (css().getPropertyValue(name) || fallback).trim();
            const money0 = (n) => '$' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 0 });
            const money2 = (n) => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const parse = (raw) => { try { return JSON.parse(raw || '[]'); } catch (e) { return []; } };

            // Ranked horizontal bars: one hue (magnitude), value labelled at the bar end.
            const barOptions = (rows, unit) => {
                const max = Math.max(1, ...rows.map((r) => r.value));
                return {
                    chart: { type: 'bar', height: Math.max(150, 46 + rows.length * 46), fontFamily: 'inherit', background: 'transparent',
                        toolbar: { show: false }, zoom: { enabled: false }, animations: { enabled: true, speed: 450, dynamicAnimation: { speed: 350 } },
                        parentHeightOffset: 0 },
                    series: [{ name: @json(__('Net sales')), data: rows.map((r) => r.value) }],
                    colors: [v('--viz-1', '#2a78d6')],
                    plotOptions: { bar: { horizontal: true, barHeight: '58%', borderRadius: 4, borderRadiusApplication: 'end',
                        dataLabels: { position: 'top' } } },
                    dataLabels: { enabled: true, formatter: money2, textAnchor: 'start', offsetX: 6,
                        style: { colors: [v('--sx-ink', '#0f1622')], fontSize: '12px', fontWeight: 700 } },
                    states: { hover: { filter: { type: 'darken', value: 0.88 } }, active: { filter: { type: 'none' } } },
                    grid: { show: false, padding: { left: 0, right: 8, top: -12, bottom: -8 } },
                    xaxis: { categories: rows.map((r) => r.label), max: max * 1.32,
                        labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: { labels: { maxWidth: 170, style: { colors: v('--sx-ink', '#0f1622'), fontSize: '12.5px', fontWeight: 600 } } },
                    tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                        y: { formatter: (n, { dataPointIndex }) => money2(n) + ' · ' + ((rows[dataPointIndex] || {}).meta ?? 0) + ' ' + unit,
                            title: { formatter: () => '' } } },
                };
            };

            const renderBars = (el, rows, unit) => {
                if (!rows.length) {
                    el.innerHTML = '<p class="rpt-bars__empty">' + @json(__('No sales in this period')) + '</p>';
                    return null;
                }
                el.innerHTML = '';
                const chart = new ApexCharts(el, barOptions(rows, unit));
                chart.render();
                charts.push(chart);
                return chart;
            };

            const boot = () => {
                destroy();
                const root = document.querySelector('[data-overview]');
                if (!root || typeof ApexCharts === 'undefined') return;

                let data = {};
                try { data = JSON.parse(root.dataset.chart || '{}'); } catch (e) { data = {}; }
                const dark = document.documentElement.classList.contains('dark');

                const trendEl = root.querySelector('[data-trend-chart]');
                if (trendEl) {
                    const trend = new ApexCharts(trendEl, {
                        chart: { type: 'area', height: 300, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false },
                            animations: { enabled: false }, background: 'transparent' },
                        series: [
                            { name: @json(__('This period')), data: data.total_sales || [] },
                            { name: @json(__('Previous period')), data: data.previous_total_sales || [] },
                        ],
                        colors: [v('--viz-1', '#2a78d6'), v('--viz-ghost', '#c3cad5')],
                        stroke: { curve: 'smooth', width: [2, 2], dashArray: [0, 5] },
                        fill: { type: ['gradient', 'solid'], gradient: { opacityFrom: 0.22, opacityTo: 0.02 }, opacity: [1, 0] },
                        markers: { size: 0, hover: { size: 5 } },
                        dataLabels: { enabled: false },
                        legend: { show: false },
                        grid: { borderColor: v('--sx-line', '#ebedf1'), strokeDashArray: 4, padding: { left: 6, right: 6 } },
                        xaxis: { categories: data.labels || [], tickAmount: Math.min(10, (data.labels || []).length),
                            labels: { rotate: 0, hideOverlappingLabels: true, style: { colors: v('--sx-muted', '#79838f'), fontSize: '11px' } },
                            axisBorder: { show: false }, axisTicks: { show: false }, crosshairs: { show: true } },
                        yaxis: { labels: { style: { colors: v('--sx-muted', '#79838f'), fontSize: '11px' }, formatter: money0 } },
                        tooltip: { shared: true, intersect: false, theme: dark ? 'dark' : 'light',
                            y: { formatter: (n, { seriesIndex, dataPointIndex }) => money2(n) + (seriesIndex === 0 ? ' · ' + ((data.orders || [])[dataPointIndex] ?? 0) + ' ' + @json(__('orders')) : '') } },
                    });
                    trend.render();
                    charts.push(trend);
                }

                // Top sellers: one chart, Products / Categories toggle swaps its data.
                const panel = root.querySelector('[data-mix-panel]');
                if (panel) {
                    const el = panel.querySelector('[data-bar-chart]');
                    const sets = { products: parse(el.dataset.products), categories: parse(el.dataset.categories) };
                    let mix = renderBars(el, sets.products, el.dataset.unit);
                    panel.querySelectorAll('[data-mix-tab]').forEach((tab) => {
                        tab.addEventListener('click', () => {
                            panel.querySelectorAll('[data-mix-tab]').forEach((t) => {
                                t.classList.toggle('is-active', t === tab);
                                t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
                            });
                            const more = panel.querySelector('[data-mix-more]');
                            if (more) more.href = tab.dataset.href;
                            const rows = sets[tab.dataset.mixTab] || [];
                            if (mix) { charts = charts.filter((c) => c !== mix); try { mix.destroy(); } catch (e) {} }
                            mix = renderBars(el, rows, el.dataset.unit);
                        });
                    });
                }

                const payEl = root.querySelector('[data-payments]');
                if (payEl) renderBars(payEl, parse(payEl.dataset.payments), payEl.dataset.unit);
            };

            if (typeof ApexCharts !== 'undefined') boot(); else document.addEventListener('DOMContentLoaded', boot);
            document.addEventListener('ajax:page-unload', destroy);
            document.addEventListener('ajax:page-loaded', boot);
        })();
    </script>
    @endpush
</x-app-layout>
