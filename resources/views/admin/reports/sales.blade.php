@php
    $money = fn ($v) => '$'.number_format((float) $v, 2);

    // Renders a compact period-over-period delta chip. `positiveGood` flips the
    // colour semantics for metrics where a rise is bad (e.g. refunds).
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
    $exportQuery = request()->query();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Analytics') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Sales Report') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page sales-analytics" data-sales-analytics>

        {{-- ===================== PAGE INTRO ===================== --}}
        <div class="sales-intro">
            <p class="sales-intro__sub">{{ __('Monitor revenue, orders, profitability, customers, and sales performance.') }}</p>
            <div class="sales-intro__actions">
                <a href="{{ route('admin.reports.sales', $exportQuery) }}" class="ghost-button" title="{{ __('Refresh') }}">
                    <i class="fa-solid fa-rotate"></i><span>{{ __('Refresh') }}</span>
                </a>
                <div class="export-menu" x-data="{ open: false }" @keydown.escape="open = false">
                    <button type="button" class="premium-button premium-button--dark" @click="open = !open" :aria-expanded="open">
                        <i class="fa-solid fa-file-export"></i><span>{{ __('Export') }}</span>
                        <i class="fa-solid fa-chevron-down export-menu__caret"></i>
                    </button>
                    <div class="export-menu__panel" x-show="open" x-transition.origin.top.right @click.outside="open = false" x-cloak>
                        <a href="{{ route('admin.reports.sales.export', ['format' => 'csv'] + $exportQuery) }}">
                            <i class="fa-solid fa-file-csv"></i>{{ __('Export CSV') }}
                        </a>
                        <a href="{{ route('admin.reports.sales.export', ['format' => 'pdf'] + $exportQuery) }}">
                            <i class="fa-solid fa-file-pdf"></i>{{ __('Export PDF') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================== FILTER TOOLBAR ===================== --}}
        <form method="GET" action="{{ route('admin.reports.sales') }}" class="sales-filters">
            <div class="sales-filters__field sales-filters__field--range">
                <span>{{ __('Date range') }}</span>
                <div class="daterange-control">
                    <i class="fa-solid fa-calendar-days"></i>
                    <input type="text" class="form-input" data-daterange data-daterange-from="start_date" data-daterange-to="end_date" placeholder="{{ __('Select report range') }}" readonly>
                    <input type="hidden" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                    <input type="hidden" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                </div>
            </div>

            {{-- Server-side customer autocomplete (never loads the full base). --}}
            <div class="sales-filters__field sales-filters__field--customer"
                 x-data="salesCustomerFilter('{{ route('admin.reports.sales.customers') }}', @js($filters['customer'] ?? ''))">
                <span>{{ __('Customer') }}</span>
                <div class="customer-select" @click.outside="close()">
                    <div class="customer-select__control" :class="{ 'is-open': open }">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" class="form-input" x-model="query" @focus="open = true; search()" @input.debounce.250ms="search()"
                               placeholder="{{ __('All customers') }}" autocomplete="off">
                        <button type="button" class="customer-select__clear" x-show="selected" @click="clear()" x-cloak title="{{ __('Clear') }}">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <input type="hidden" name="customer" :value="selected">
                    <div class="customer-select__menu" x-show="open" x-transition x-cloak>
                        <button type="button" class="customer-select__opt" @click="choose(null)">
                            <span class="customer-select__name">{{ __('All customers') }}</span>
                        </button>
                        <template x-if="loading">
                            <div class="customer-select__hint">{{ __('Searching…') }}</div>
                        </template>
                        <template x-for="row in results" :key="row.value">
                            <button type="button" class="customer-select__opt" @click="choose(row)">
                                <span class="customer-select__name" x-text="row.name"></span>
                                <span class="customer-select__mail" x-text="row.email"></span>
                            </button>
                        </template>
                        <template x-if="!loading && results.length === 0 && query">
                            <div class="customer-select__hint">{{ __('No customers found') }}</div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="sales-filters__field">
                <span>{{ __('Order status') }}</span>
                <x-select name="status" size="sm" :value="$filters['status'] ?? null" placeholder="{{ __('All statuses') }}" :options="$orderStatuses" />
            </div>

            <div class="sales-filters__field">
                <span>{{ __('Payment status') }}</span>
                <x-select name="payment_status" size="sm" :value="$filters['payment_status'] ?? null" placeholder="{{ __('All payments') }}" :options="$paymentStatuses" />
            </div>

            <div class="sales-filters__actions">
                <a href="{{ route('admin.reports.sales') }}" class="ghost-button">
                    <i class="fa-solid fa-rotate-left"></i><span>{{ __('Reset') }}</span>
                </a>
                <button type="submit" class="filter-button">
                    <i class="fa-solid fa-filter"></i><span>{{ __('Apply') }}</span>
                </button>
            </div>
        </form>

        <p class="sales-period">
            <i class="fa-solid fa-circle-info"></i>
            {{ __('Showing') }} <strong>{{ \Carbon\Carbon::parse($filters['start_date'])->format('M d, Y') }} – {{ \Carbon\Carbon::parse($filters['end_date'])->format('M d, Y') }}</strong>
            <span class="sales-period__prev">{{ __('vs') }} {{ \Carbon\Carbon::parse($previousRange['start_date'])->format('M d') }} – {{ \Carbon\Carbon::parse($previousRange['end_date'])->format('M d, Y') }}</span>
            @if (! empty($filters['customer']))
                <span class="sales-period__chip"><i class="fa-solid fa-user"></i> {{ $filters['customer'] }}</span>
            @endif
        </p>

        @if (! $hasData)
            {{-- ===================== EMPTY STATE ===================== --}}
            <div class="premium-card sales-empty">
                <i class="fa-solid fa-chart-line"></i>
                <h3>{{ __('No sales data for this period') }}</h3>
                <p>{{ __('Try changing your date range or filters to see performance.') }}</p>
                <a href="{{ route('admin.reports.sales') }}" class="premium-button premium-button--dark">
                    <i class="fa-solid fa-sliders"></i>{{ __('Adjust filters') }}
                </a>
            </div>
        @else

        {{-- ===================== PRIMARY KPIs ===================== --}}
        <div class="kpi-grid">
            <article class="kpi-card kpi-card--primary">
                <header><span>{{ __('Total revenue') }}</span><i class="fa-solid fa-sack-dollar"></i></header>
                <strong class="kpi-value">{{ $money($summary['total_revenue']) }}</strong>
                <footer>{!! $delta($comparison['total_revenue'] ?? null) !!}<span class="kpi-vs">{{ __('vs prev.') }}</span></footer>
                <div class="kpi-spark" data-spark="revenue"></div>
            </article>
            <article class="kpi-card">
                <header><span>{{ __('Average order') }}</span><i class="fa-solid fa-receipt"></i></header>
                <strong class="kpi-value">{{ $money($summary['average_order']) }}</strong>
                <footer>{!! $delta($comparison['average_order'] ?? null) !!}<span class="kpi-vs">{{ __('vs prev.') }}</span></footer>
            </article>
            <article class="kpi-card">
                <header><span>{{ __('Orders') }}</span><i class="fa-solid fa-bag-shopping"></i></header>
                <strong class="kpi-value">{{ number_format($summary['orders']) }}</strong>
                <footer>{!! $delta($comparison['orders'] ?? null) !!}<span class="kpi-vs">{{ __('vs prev.') }}</span></footer>
                <div class="kpi-spark" data-spark="orders"></div>
            </article>
            <article class="kpi-card">
                <header><span>{{ __('Gross profit') }}</span><i class="fa-solid fa-arrow-trend-up"></i></header>
                <strong class="kpi-value">{{ $money($summary['gross_profit']) }}</strong>
                <footer>{!! $delta($comparison['gross_profit'] ?? null) !!}<span class="kpi-badge">{{ number_format($summary['margin'], 1) }}% {{ __('margin') }}</span></footer>
                <div class="kpi-spark" data-spark="profit"></div>
            </article>
        </div>

        {{-- ===================== PERFORMANCE CHART ===================== --}}
        <section class="premium-card sales-chart">
            <header class="sales-chart__head">
                <div>
                    <h3>{{ __('Sales Performance') }}</h3>
                    <p>{{ __('Revenue and order trends over time') }}</p>
                </div>
                <div class="sales-chart__toggle" role="tablist">
                    <button type="button" class="is-active" data-metric="revenue">{{ __('Revenue') }}</button>
                    <button type="button" data-metric="orders">{{ __('Orders') }}</button>
                    <button type="button" data-metric="profit">{{ __('Profit') }}</button>
                </div>
            </header>
            <div class="sales-chart__canvas" data-sales-chart></div>
        </section>

        {{-- ===================== SECONDARY METRICS =====================
             Only metrics not already on a KPI card or in the Revenue & Profit
             waterfall: gross/net sales, margin and AOV live there. --}}
        <div class="metric-strip">
            <div><span>{{ __('Paid orders') }}</span><strong>{{ number_format($summary['paid_orders']) }}</strong></div>
            <div><span>{{ __('Discounts') }}</span><strong>{{ $money($summary['discount_total']) }}</strong></div>
            <div><span>{{ __('Refunds') }}</span><strong>{{ $money($summary['refunds']) }} <em class="metric-strip__chip">{{ number_format($refunds['rate'], 1) }}%</em></strong></div>
            <div><span>{{ __('Tax collected') }}</span><strong>{{ $money($summary['tax_total']) }}</strong></div>
            <div><span>{{ __('Shipping') }}</span><strong>{{ $money($summary['shipping_total']) }}</strong></div>
        </div>

        {{-- ===================== BUSINESS ANALYSIS ===================== --}}
        <div class="analysis-grid">
            {{-- Revenue & profit waterfall --}}
            <section class="premium-card analysis-card">
                <h3>{{ __('Revenue & Profit') }}</h3>
                <ul class="profit-flow">
                    @php
                        $flowMax = max($summary['total_revenue'], 1);
                        $flow = [
                            ['Gross sales', $summary['gross_sales'], 'is-base'],
                            ['Net sales', $summary['net_sales'], 'is-net'],
                            ['Total revenue', $summary['total_revenue'], 'is-rev'],
                            ['Gross profit', $summary['gross_profit'], 'is-profit'],
                        ];
                    @endphp
                    @foreach ($flow as [$label, $val, $cls])
                        <li>
                            <div class="profit-flow__top"><span>{{ __($label) }}</span><strong>{{ $money($val) }}</strong></div>
                            <div class="profit-flow__bar"><span class="{{ $cls }}" style="width: {{ min(100, max(2, round(($val / $flowMax) * 100))) }}%"></span></div>
                        </li>
                    @endforeach
                </ul>
                <div class="profit-flow__margin">
                    <span>{{ __('Profit margin') }}</span>
                    <strong>{{ number_format($summary['margin'], 1) }}%</strong>
                </div>
            </section>

            {{-- Sales by category --}}
            <section class="premium-card analysis-card">
                <h3>{{ __('Sales by Category') }}</h3>
                @forelse ($categories as $cat)
                    <div class="rank-row">
                        <div class="rank-row__label">
                            <span class="rank-row__name">{{ $cat['category'] }}</span>
                            <span class="rank-row__meta">{{ number_format($cat['units']) }} {{ __('units') }} · {{ $money($cat['revenue']) }}</span>
                        </div>
                        <div class="rank-row__bar"><span style="width: {{ max(3, $cat['percent']) }}%"></span></div>
                        <span class="rank-row__pct">{{ number_format($cat['percent'], 1) }}%</span>
                    </div>
                @empty
                    <p class="analysis-empty">{{ __('No category sales in range.') }}</p>
                @endforelse
            </section>

            {{-- Payment methods --}}
            <section class="premium-card analysis-card">
                <h3>{{ __('Payment Methods') }}</h3>
                <div class="paymethod-donut" data-paymethod-chart></div>
                <ul class="paymethod-legend">
                    @forelse ($paymentMethods as $pm)
                        <li>
                            <span class="paymethod-legend__dot" data-pm-dot></span>
                            <span class="paymethod-legend__name">{{ $pm['method'] }}</span>
                            <span class="paymethod-legend__meta">{{ number_format($pm['orders']) }} · {{ $money($pm['revenue']) }}</span>
                            <span class="paymethod-legend__pct">{{ number_format($pm['percent'], 1) }}%</span>
                        </li>
                    @empty
                        <li class="analysis-empty">{{ __('No payment data in range.') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>

        {{-- ===================== TRANSACTIONS ===================== --}}
        @php
            $sortLink = function (string $key) {
                $asc = request('sort') === $key && request('direction') === 'asc';
                return [request()->fullUrlWithQuery(['sort' => $key, 'direction' => $asc ? 'desc' : 'asc']), request('sort') === $key ? (request('direction') === 'asc' ? 'up' : 'down') : null];
            };
            [$dLink, $dDir] = $sortLink('date');
            [$gLink, $gDir] = $sortLink('gross');
            [$nLink, $nDir] = $sortLink('net');
        @endphp
        <section class="premium-card admin-table-card sales-transactions" data-ajax-table>
            <x-table-toolbar>
                <x-slot:left>
                    <div class="panel-head panel-head--flush">
                        <h3>{{ __('Sales Transactions') }}</h3>
                        <span>{{ __('Order-level detail behind the summary') }}</span>
                    </div>
                </x-slot:left>
                <x-slot:right>
                    <x-per-page-selector :current="$perPage" />
                    <x-search-input name="search" placeholder="{{ __('Search order or customer…') }}" />
                </x-slot:right>
            </x-table-toolbar>

            <div data-ajax-region>
                <div class="premium-table-wrap admin-table-card__scroll">
                    <table class="premium-table sales-tx-table">
                        <thead>
                            <tr>
                                <th><a href="{{ $dLink }}" class="th-sort {{ $dDir ? 'is-'.$dDir : '' }}">{{ __('Date') }}<i class="fa-solid fa-sort"></i></a></th>
                                <th>{{ __('Order') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Payment') }}</th>
                                <th class="ta-r"><a href="{{ $gLink }}" class="th-sort {{ $gDir ? 'is-'.$gDir : '' }}">{{ __('Gross') }}<i class="fa-solid fa-sort"></i></a></th>
                                <th class="ta-r"><a href="{{ $nLink }}" class="th-sort {{ $nDir ? 'is-'.$nDir : '' }}">{{ __('Net sales') }}<i class="fa-solid fa-sort"></i></a></th>
                                <th class="ta-r">{{ __('Profit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $tx)
                                <tr>
                                    <td class="nowrap">{{ $tx['date'] }}</td>
                                    <td>
                                        @if (\Illuminate\Support\Facades\Route::has('admin.orders.show'))
                                            <a href="{{ route('admin.orders.show', $tx['order_id']) }}" class="tx-order">#{{ $tx['order_number'] }}</a>
                                        @else
                                            <span class="tx-order">#{{ $tx['order_number'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="cust-cell">
                                            <strong>{{ $tx['customer_name'] }}
                                                @if ($tx['is_guest'])<span class="guest-tag">{{ __('Guest') }}</span>@endif
                                            </strong>
                                            @if ($tx['customer_email'])<small>{{ $tx['customer_email'] }}</small>@endif
                                        </div>
                                    </td>
                                    <td><span class="status-chip {{ $tx['status']->badge() }}">{{ $tx['status']->label() }}</span></td>
                                    <td><span class="status-chip {{ $tx['payment_status']->badge() }}">{{ $tx['payment_status']->label() }}</span></td>
                                    <td class="ta-r">{{ $money($tx['gross']) }}</td>
                                    <td class="ta-r"><strong>{{ $money($tx['net_sales']) }}</strong></td>
                                    <td class="ta-r {{ $tx['profit'] >= 0 ? 'money-pos' : 'money-neg' }}">{{ $money($tx['profit']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <x-admin.empty-state icon="fa-solid fa-receipt" title="{{ __('No transactions found') }}" message="{{ __('Try a different date range, customer, or status filter.') }}" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <x-table-footer :paginator="$transactions" label="{{ __('orders') }}" />
            </div>
        </section>
        @endif
    </div>

    @push('js')
    @php
        $chartSeries = $series ?? ['labels' => [], 'revenue' => [], 'orders' => [], 'profit' => []];
        $chartPayMethods = ($paymentMethods ?? collect())->values();
    @endphp
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('salesCustomerFilter', (url, initial) => ({
                url, open: false, query: '', selected: initial || '', loading: false, results: [],
                init() { if (this.selected) { this.query = this.selected; } },
                async search() {
                    this.loading = true;
                    try {
                        const res = await fetch(`${this.url}?q=${encodeURIComponent(this.query)}`, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        this.results = data.results || [];
                    } catch (e) { this.results = []; }
                    this.loading = false;
                },
                choose(row) {
                    if (row === null) { this.selected = ''; this.query = ''; }
                    else { this.selected = row.value; this.query = row.name; }
                    this.open = false;
                    this.$root.closest('form').requestSubmit();
                },
                clear() { this.choose(null); },
                close() { this.open = false; },
            }));
        });

        (function () {
            const root = document.querySelector('[data-sales-analytics]');
            if (!root || typeof ApexCharts === 'undefined') return;

            const SERIES = @json($chartSeries);
            const PAYMETHODS = @json($chartPayMethods);
            const INK = '#0f172a', GRID = '#eef1f6', MUTED = '#64748b';
            const PALETTE = ['#0f172a', '#0ea5e9', '#10b981', '#f59e0b', '#8b5cf6', '#f43f5e', '#14b8a6'];
            const money = (v) => '$' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            const labels = (SERIES.labels || []).map(d => {
                const dt = new Date(d + 'T00:00:00');
                return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });

            const METRICS = {
                revenue: { name: '{{ __('Revenue') }}', data: SERIES.revenue || [], color: '#0ea5e9', money: true },
                orders:  { name: '{{ __('Orders') }}',  data: SERIES.orders || [],  color: '#0f172a', money: false },
                profit:  { name: '{{ __('Profit') }}',  data: SERIES.profit || [],  color: '#10b981', money: true },
            };

            const el = root.querySelector('[data-sales-chart]');
            if (!el) return; // empty state: no chart to render
            let current = 'revenue';
            let chart = null;

            function options(key) {
                const m = METRICS[key];
                return {
                    chart: { type: 'area', height: 320, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false },
                        animations: { enabled: true, easing: 'easeinout', speed: 500 } },
                    series: [{ name: m.name, data: m.data }],
                    colors: [m.color],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2.5 },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 90] } },
                    grid: { borderColor: GRID, strokeDashArray: 4, padding: { left: 8, right: 8 } },
                    xaxis: { categories: labels, tickAmount: Math.min(12, labels.length), labels: { style: { colors: MUTED, fontSize: '11px' } },
                        axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: { labels: { style: { colors: MUTED, fontSize: '11px' }, formatter: (v) => m.money ? money(v) : Math.round(v) } },
                    tooltip: { y: { formatter: (v) => m.money ? '$' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2 }) : Math.round(v) + ' {{ __('orders') }}' } },
                };
            }

            chart = new ApexCharts(el, options(current));
            chart.render();

            root.querySelectorAll('.sales-chart__toggle button').forEach(btn => {
                btn.addEventListener('click', () => {
                    root.querySelectorAll('.sales-chart__toggle button').forEach(b => b.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    current = btn.dataset.metric;
                    chart.updateOptions(options(current), true, true);
                });
            });

            // KPI sparklines
            root.querySelectorAll('[data-spark]').forEach(node => {
                const key = node.dataset.spark;
                const m = METRICS[key];
                if (!m || !m.data.length) return;
                new ApexCharts(node, {
                    chart: { type: 'area', height: 44, sparkline: { enabled: true }, animations: { enabled: false } },
                    series: [{ name: m.name, data: m.data }],
                    colors: [m.color],
                    stroke: { curve: 'smooth', width: 1.8 },
                    fill: { type: 'gradient', gradient: { opacityFrom: 0.25, opacityTo: 0 } },
                    tooltip: { enabled: false },
                }).render();
            });

            // Payment-method donut
            const donutEl = root.querySelector('[data-paymethod-chart]');
            if (donutEl && PAYMETHODS.length) {
                new ApexCharts(donutEl, {
                    chart: { type: 'donut', height: 180, fontFamily: 'inherit' },
                    series: PAYMETHODS.map(p => Math.round(p.revenue)),
                    labels: PAYMETHODS.map(p => p.method),
                    colors: PALETTE,
                    legend: { show: false },
                    dataLabels: { enabled: false },
                    stroke: { width: 2, colors: ['#fff'] },
                    plotOptions: { pie: { donut: { size: '68%', labels: { show: true,
                        total: { show: true, label: '{{ __('Revenue') }}', color: MUTED, fontSize: '11px',
                            formatter: () => money(PAYMETHODS.reduce((a, p) => a + p.revenue, 0)) },
                        value: { color: INK, fontSize: '18px', fontWeight: 700, formatter: (v) => money(v) } } } } },
                    tooltip: { y: { formatter: (v) => money(v) } },
                }).render();
                // colour the legend dots to match slices
                donutEl.closest('.analysis-card').querySelectorAll('[data-pm-dot]').forEach((dot, i) => {
                    dot.style.background = PALETTE[i % PALETTE.length];
                });
            }
        })();
    </script>
    @endpush

    <style>
        [x-cloak] { display: none !important; }
        .sales-intro { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; }
        .sales-intro__sub { font-size: 0.85rem; color: #64748b; margin: 0; }
        .sales-intro__actions { display: flex; gap: 0.55rem; flex-shrink: 0; margin-left: auto; }

        .export-menu { position: relative; }
        .export-menu__caret { font-size: 0.65rem; margin-left: 0.15rem; }
        .export-menu__panel { position: absolute; right: 0; top: calc(100% + 6px); z-index: 40; min-width: 180px;
            background: #fff; border: 1px solid #e6e9f0; border-radius: 12px; box-shadow: 0 18px 40px -18px rgba(15,23,42,.35); padding: 6px; }
        .export-menu__panel a { display: flex; align-items: center; gap: 0.6rem; padding: 0.55rem 0.7rem; border-radius: 8px;
            font-size: 0.83rem; color: #334155; text-decoration: none; }
        .export-menu__panel a:hover { background: #f4f6fb; color: #0f172a; }
        .export-menu__panel i { width: 16px; color: #94a3b8; }

        .sales-analytics { display: flex; flex-direction: column; gap: 1.15rem; }

        /* Filter toolbar */
        .sales-filters { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.8rem;
            background: #fff; border: 1px solid #e9edf4; border-radius: 16px; padding: 0.95rem 1.05rem; box-shadow: 0 1px 2px rgba(16,24,40,.03); }
        .sales-filters__field { display: flex; flex-direction: column; gap: 0.35rem; min-width: 170px; }
        .sales-filters__field > span { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; }
        .sales-filters__field--range { min-width: 240px; }
        .sales-filters__field--customer { min-width: 230px; }
        .sales-filters__actions { display: flex; gap: 0.5rem; margin-left: auto; }
        .daterange-control { position: relative; display: flex; align-items: center; }
        .daterange-control > i { position: absolute; left: 0.7rem; color: #94a3b8; font-size: 0.8rem; pointer-events: none; }
        .daterange-control .form-input { padding-left: 2rem; cursor: pointer; }

        /* Customer autocomplete */
        .customer-select { position: relative; }
        .customer-select__control { position: relative; display: flex; align-items: center; }
        .customer-select__control > i { position: absolute; left: 0.7rem; color: #94a3b8; font-size: 0.8rem; }
        .customer-select__control .form-input { padding-left: 2rem; padding-right: 2rem; width: 100%; }
        .customer-select__clear { position: absolute; right: 0.55rem; border: none; background: transparent; color: #94a3b8; cursor: pointer; }
        .customer-select__clear:hover { color: #f43f5e; }
        .customer-select__menu { position: absolute; z-index: 45; top: calc(100% + 5px); left: 0; right: 0; max-height: 260px; overflow-y: auto;
            background: #fff; border: 1px solid #e6e9f0; border-radius: 12px; box-shadow: 0 20px 44px -20px rgba(15,23,42,.4); padding: 5px; }
        .customer-select__opt { display: flex; flex-direction: column; gap: 1px; width: 100%; text-align: left; border: none; background: transparent;
            padding: 0.5rem 0.6rem; border-radius: 8px; cursor: pointer; }
        .customer-select__opt:hover { background: #f4f6fb; }
        .customer-select__name { font-size: 0.83rem; font-weight: 600; color: #1e293b; }
        .customer-select__mail { font-size: 0.72rem; color: #94a3b8; }
        .customer-select__hint { padding: 0.6rem; font-size: 0.78rem; color: #94a3b8; text-align: center; }

        .sales-period { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #64748b; margin: -0.2rem 0 0; }
        .sales-period i { color: #94a3b8; }
        .sales-period strong { color: #334155; }
        .sales-period__prev { color: #94a3b8; }
        .sales-period__chip { display: inline-flex; align-items: center; gap: 0.35rem; background: #eef2ff; color: #4338ca;
            padding: 0.2rem 0.55rem; border-radius: 999px; font-weight: 600; font-size: 0.74rem; }

        /* KPI cards */
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
        .kpi-card footer { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
        .kpi-vs { font-size: 0.72rem; color: #94a3b8; }
        .kpi-delta { display: inline-flex; align-items: center; gap: 0.28rem; font-size: 0.76rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 999px; }
        .kpi-delta.is-up { color: #059669; background: #ecfdf5; }
        .kpi-delta.is-down { color: #e11d48; background: #fef2f2; }
        .kpi-delta.is-flat { color: #64748b; background: #f1f5f9; }
        .kpi-badge { font-size: 0.72rem; font-weight: 600; color: #475569; background: #eef2f7; padding: 0.15rem 0.45rem; border-radius: 999px; }
        .kpi-card--primary .kpi-badge { background: rgba(255,255,255,.14); color: #e2e8f0; }
        .kpi-spark { margin: 0.5rem -1.1rem 0; opacity: .9; }

        /* Chart */
        .sales-chart { padding: 1.15rem 1.25rem 0.6rem; }
        .sales-chart__head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
        .sales-chart__head h3 { font-size: 1.02rem; font-weight: 650; color: #0f172a; margin: 0; }
        .sales-chart__head p { font-size: 0.8rem; color: #94a3b8; margin: 0.2rem 0 0; }
        .sales-chart__toggle { display: inline-flex; background: #f1f5f9; border-radius: 10px; padding: 3px; gap: 2px; }
        .sales-chart__toggle button { border: none; background: transparent; font-size: 0.78rem; font-weight: 600; color: #64748b;
            padding: 0.35rem 0.85rem; border-radius: 8px; cursor: pointer; transition: all .15s; }
        .sales-chart__toggle button.is-active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(16,24,40,.12); }
        .sales-chart__canvas { margin-top: 0.4rem; }

        /* Secondary metrics */
        .metric-strip { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0; background: #fff; border: 1px solid #e9edf4; border-radius: 16px; overflow: hidden; }
        .metric-strip > div { padding: 0.85rem 1rem; border-right: 1px solid #eef1f6; border-bottom: 1px solid #eef1f6; }
        .metric-strip > div:nth-child(5n) { border-right: none; }
        .metric-strip > div:nth-last-child(-n+5) { border-bottom: none; }
        .metric-strip span { display: block; font-size: 0.72rem; color: #94a3b8; font-weight: 600; margin-bottom: 0.2rem; }
        .metric-strip strong { font-size: 1.02rem; font-weight: 650; color: #1e293b; }
        .metric-strip__chip { font-style: normal; font-size: 0.7rem; font-weight: 700; color: #e11d48; background: #fef2f2; padding: 0.1rem 0.4rem; border-radius: 999px; vertical-align: middle; }

        /* Analysis grid */
        .analysis-grid { display: grid; grid-template-columns: 1.1fr 1.1fr 0.9fr; gap: 0.9rem; }
        .analysis-card { padding: 1.1rem 1.2rem; }
        .analysis-card h3 { font-size: 0.95rem; font-weight: 650; color: #0f172a; margin: 0 0 0.9rem; }
        .analysis-empty { font-size: 0.82rem; color: #94a3b8; padding: 0.8rem 0; margin: 0; }

        .profit-flow { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.75rem; }
        .profit-flow__top { display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 0.3rem; }
        .profit-flow__top span { color: #64748b; }
        .profit-flow__top strong { color: #1e293b; font-weight: 650; }
        .profit-flow__bar { height: 8px; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
        .profit-flow__bar span { display: block; height: 100%; border-radius: 999px; }
        .profit-flow__bar .is-base { background: #cbd5e1; }
        .profit-flow__bar .is-net { background: #0ea5e9; }
        .profit-flow__bar .is-rev { background: #6366f1; }
        .profit-flow__bar .is-profit { background: #10b981; }
        .profit-flow__margin { display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 0.85rem; border-top: 1px dashed #e2e8f0; }
        .profit-flow__margin span { font-size: 0.82rem; color: #64748b; }
        .profit-flow__margin strong { font-size: 1.25rem; font-weight: 700; color: #10b981; }

        .rank-row { display: grid; grid-template-columns: 1fr 90px 44px; align-items: center; gap: 0.7rem; padding: 0.5rem 0; }
        .rank-row + .rank-row { border-top: 1px solid #f1f5f9; }
        .rank-row__name { display: block; font-size: 0.84rem; font-weight: 600; color: #1e293b; }
        .rank-row__meta { font-size: 0.72rem; color: #94a3b8; }
        .rank-row__bar { height: 6px; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
        .rank-row__bar span { display: block; height: 100%; background: linear-gradient(90deg, #0ea5e9, #6366f1); border-radius: 999px; }
        .rank-row__pct { font-size: 0.78rem; font-weight: 650; color: #475569; text-align: right; }

        .paymethod-donut { display: flex; justify-content: center; margin-bottom: 0.5rem; }
        .paymethod-legend { list-style: none; margin: 0; padding: 0; }
        .paymethod-legend li { display: grid; grid-template-columns: 12px 1fr auto; align-items: center; column-gap: 0.5rem; padding: 0.4rem 0; font-size: 0.8rem; }
        .paymethod-legend li + li { border-top: 1px solid #f1f5f9; }
        .paymethod-legend__dot { width: 9px; height: 9px; border-radius: 3px; background: #cbd5e1; }
        .paymethod-legend__name { font-weight: 600; color: #334155; }
        .paymethod-legend__meta { grid-column: 2; font-size: 0.7rem; color: #94a3b8; }
        .paymethod-legend__pct { grid-row: 1 / span 2; font-weight: 650; color: #475569; }

        /* Panel heads */
        .panel-head { display: flex; justify-content: space-between; align-items: baseline; padding: 1.05rem 1.2rem 0.75rem; }
        .panel-head--flush { padding: 0; }
        .panel-head h3 { font-size: 0.95rem; font-weight: 650; color: #0f172a; margin: 0; }
        .panel-head span { font-size: 0.74rem; color: #94a3b8; }
        .ta-r { text-align: right; }
        .cust-cell strong { display: block; font-size: 0.82rem; color: #1e293b; font-weight: 600; }
        .cust-cell small { font-size: 0.72rem; color: #94a3b8; }
        .money-pos { color: #059669; font-weight: 600; }
        .money-neg { color: #e11d48; }
        .money-muted { color: #94a3b8; }
        .guest-tag { display: inline-block; font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;
            color: #a16207; background: #fef9c3; padding: 0.05rem 0.35rem; border-radius: 5px; margin-left: 0.35rem; vertical-align: middle; }

        /* Transactions */
        .sales-transactions { padding: 0; }
        .sales-transactions .table-toolbar { padding: 1.05rem 1.2rem; }
        .sales-tx-table th, .sales-tx-table td { padding: 0.62rem 0.8rem; white-space: nowrap; }
        .sales-tx-table td.nowrap { color: #64748b; font-size: 0.8rem; }
        .tx-order { font-weight: 650; color: #4338ca; text-decoration: none; font-size: 0.82rem; }
        .tx-order:hover { text-decoration: underline; }
        .th-sort { display: inline-flex; align-items: center; gap: 0.3rem; color: inherit; text-decoration: none; }
        .th-sort i { font-size: 0.65rem; color: #cbd5e1; }
        .th-sort.is-up i, .th-sort.is-down i { color: #0f172a; }
        .status-chip { display: inline-block; font-size: 0.7rem; font-weight: 650; padding: 0.18rem 0.5rem; border-radius: 6px; }

        /* Empty */
        .sales-empty { text-align: center; padding: 3rem 1.5rem; display: flex; flex-direction: column; align-items: center; gap: 0.6rem; }
        .sales-empty i { font-size: 2rem; color: #cbd5e1; }
        .sales-empty h3 { font-size: 1.05rem; font-weight: 650; color: #334155; margin: 0.3rem 0 0; }
        .sales-empty p { font-size: 0.85rem; color: #94a3b8; margin: 0 0 0.6rem; }

        @media (max-width: 1180px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .analysis-grid { grid-template-columns: 1fr; }
            .metric-strip { grid-template-columns: repeat(2, 1fr); }
            .metric-strip > div { border-right: 1px solid #eef1f6; }
            .metric-strip > div:nth-child(2n) { border-right: none; }
        }
        @media (max-width: 720px) {
            .kpi-grid { grid-template-columns: 1fr; }
            .sales-intro { flex-direction: column; align-items: flex-start; }
            .sales-filters__actions { margin-left: 0; width: 100%; }
        }
    </style>
</x-app-layout>
