@php
    $money = fn ($v) => '$'.number_format((float) $v, 2);
    $hasData = $summary['orders'] > 0;
    $prev = $filters->previous();
    $productMax = max(1, (float) collect($topProducts)->max('net_sales'));
    $categoryMax = max(1, (float) collect($topCategories)->max('net_sales'));
    $methodMax = max(1, (float) collect($paymentMix)->max('total_sales'));
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

            {{-- Mix --}}
            <div class="rpt-grid rpt-grid--three">
                <section class="rpt-panel">
                    <header class="rpt-panel__head">
                        <div><h3>{{ __('Top products') }}</h3><p>{{ __('By net sales') }}</p></div>
                        @can('view sales reports')
                            <a href="{{ route('admin.reports.sales', ['view' => 'products'] + $filters->toQuery()) }}">{{ __('All') }} <i class="fa-solid fa-arrow-right"></i></a>
                        @endcan
                    </header>
                    <div class="rpt-rank">
                        @foreach ($topProducts as $row)
                            <div class="rpt-rank__row">
                                <span class="rpt-rank__name">{{ $row['name'] }}<small>{{ number_format($row['quantity']) }} {{ __('units') }}</small></span>
                                <span class="rpt-rank__value">{{ $money($row['net_sales']) }}</span>
                                <span class="rpt-rank__bar"><i style="width: {{ max(0, round($row['net_sales'] / $productMax * 100)) }}%"></i></span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rpt-panel">
                    <header class="rpt-panel__head">
                        <div><h3>{{ __('Top categories') }}</h3><p>{{ __('By net sales') }}</p></div>
                        @can('view sales reports')
                            <a href="{{ route('admin.reports.sales', ['view' => 'categories'] + $filters->toQuery()) }}">{{ __('All') }} <i class="fa-solid fa-arrow-right"></i></a>
                        @endcan
                    </header>
                    <div class="rpt-rank">
                        @foreach ($topCategories as $row)
                            <div class="rpt-rank__row">
                                <span class="rpt-rank__name">{{ $row['name'] }}<small>{{ number_format($row['quantity']) }} {{ __('units') }}</small></span>
                                <span class="rpt-rank__value">{{ $money($row['net_sales']) }}</span>
                                <span class="rpt-rank__bar"><i style="width: {{ max(0, round($row['net_sales'] / $categoryMax * 100)) }}%"></i></span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="rpt">
                    <section class="rpt-panel">
                        <header class="rpt-panel__head">
                            <div><h3>{{ __('Payment methods') }}</h3><p>{{ __('Total sales by method') }}</p></div>
                        </header>
                        <div class="rpt-rank">
                            @foreach ($paymentMix as $row)
                                <div class="rpt-rank__row">
                                    <span class="rpt-rank__name">{{ $row['method'] }}<small>{{ trans_choice('{1} :count order|[2,*] :count orders', $row['orders'], ['count' => $row['orders']]) }}</small></span>
                                    <span class="rpt-rank__value">{{ $money($row['total_sales']) }}</span>
                                    <span class="rpt-rank__bar"><i style="width: {{ max(0, round($row['total_sales'] / $methodMax * 100)) }}%"></i></span>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="rpt-panel">
                        <header class="rpt-panel__head">
                            <div><h3>{{ __('New vs returning') }}</h3><p>{{ __('Buyers in the period') }}</p></div>
                        </header>
                        <div class="rpt-split" role="img" aria-label="{{ __('New customers') }} {{ $customerMix['new']['customers'] }}, {{ __('Returning') }} {{ $customerMix['returning']['customers'] }}">
                            <i style="width: {{ $buyers ? $customerMix['new']['customers'] / $buyers * 100 : 0 }}%"></i>
                            <i style="width: {{ $buyers ? $customerMix['returning']['customers'] / $buyers * 100 : 0 }}%"></i>
                        </div>
                        <div class="rpt-split-legend">
                            <div><span>{{ __('New') }}</span><strong>{{ number_format($customerMix['new']['customers']) }}</strong>{{ $money($customerMix['new']['total_sales']) }}</div>
                            <div><span>{{ __('Returning') }}</span><strong>{{ number_format($customerMix['returning']['customers']) }}</strong>{{ $money($customerMix['returning']['total_sales']) }}</div>
                        </div>
                    </section>
                </div>
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
            let chart = null;
            const destroy = () => { if (chart) { try { chart.destroy(); } catch (e) {} chart = null; } };

            const boot = () => {
                destroy();
                const root = document.querySelector('[data-overview]');
                const el = root ? root.querySelector('[data-trend-chart]') : null;
                if (!el || typeof ApexCharts === 'undefined') return;

                let data = {};
                try { data = JSON.parse(root.dataset.chart || '{}'); } catch (e) { data = {}; }
                const css = getComputedStyle(document.documentElement);
                const v = (name, fallback) => (css.getPropertyValue(name) || fallback).trim();
                const dark = document.documentElement.classList.contains('dark');
                const money0 = (n) => '$' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 0 });
                const money2 = (n) => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                chart = new ApexCharts(el, {
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
                chart.render();
            };

            if (typeof ApexCharts !== 'undefined') boot(); else document.addEventListener('DOMContentLoaded', boot);
            document.addEventListener('ajax:page-unload', destroy);
            document.addEventListener('ajax:page-loaded', boot);
        })();
    </script>
    @endpush
</x-app-layout>
