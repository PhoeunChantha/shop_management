@php
    $money = fn ($v) => '$'.number_format((float) $v, 2);

    // Compact period-over-period delta chip — same semantics as the Reports Overview page.
    $delta = function (?array $c) {
        if (! $c || $c['change'] === null) {
            return '<span class="kpi-delta is-flat"><i class="fa-solid fa-minus"></i>'.__('No prior data').'</span>';
        }
        $up = $c['direction'] === 'up';
        $cls = $c['direction'] === 'flat' ? 'is-flat' : ($up ? 'is-up' : 'is-down');
        $icon = $up ? 'fa-arrow-trend-up' : ($c['direction'] === 'down' ? 'fa-arrow-trend-down' : 'fa-minus');
        return '<span class="kpi-delta '.$cls.'"><i class="fa-solid '.$icon.'"></i>'.number_format(abs($c['change']), 1).'%</span>';
    };

    $marginClass = fn (float $m) => $m < 15 ? 'is-danger' : ($m < 35 ? 'is-warn' : 'is-good');
    $badgeLabel = fn (?string $b) => match ($b) {
        'best-seller' => __('Best seller'),
        'slow-mover' => __('Slow mover'),
        default => null,
    };

    $activeSort = $filters['sort'] ?? 'revenue';
    $activeDir = $filters['direction'] ?? 'desc';
    $sortLink = function (string $key) use ($activeSort, $activeDir) {
        $asc = $activeSort === $key && $activeDir === 'asc';
        return [
            request()->fullUrlWithQuery(['sort' => $key, 'direction' => $asc ? 'desc' : 'asc']),
            $activeSort === $key ? ($activeDir === 'asc' ? 'up' : 'down') : null,
        ];
    };
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Analytics') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Product Report') }}</h2>
        </div>
    </x-slot>

    <x-admin.report-shell
        active="products"
        :title="__('Product Report')"
        :filters="$filters"
        :action="route('admin.reports.products')"
        :export-url="route('admin.reports.products.export', request()->query())"
        :pdf-url="route('admin.reports.products.export', ['format' => 'pdf'] + request()->query())"
        show-status
        show-payment
        :order-statuses="$orderStatuses"
        :payment-statuses="$paymentStatuses">

        <x-slot:controls>
            <div class="report-controlbar__field">
                <span>{{ __('Category') }}</span>
                <x-select name="category_id" size="sm" :value="$filters['category_id'] ?? null" placeholder="{{ __('All categories') }}" :options="$categories" searchable />
            </div>
        </x-slot:controls>

        <div class="pr" data-product-report data-chart="{{ json_encode($chart) }}">
            <div class="kpi-grid pr-kpi-grid">
                <article class="kpi-card">
                    <header><span>{{ __('Products sold') }}</span><i class="fa-solid fa-box"></i></header>
                    <strong class="kpi-value">{{ number_format($summary['products']) }}</strong>
                </article>
                <article class="kpi-card">
                    <header><span>{{ __('Units sold') }}</span><i class="fa-solid fa-cubes"></i></header>
                    <strong class="kpi-value">{{ number_format($summary['units']) }}</strong>
                    <footer>{!! $delta($comparison['units'] ?? null) !!}<span class="kpi-vs">{{ __('vs prev.') }}</span></footer>
                </article>
                <article class="kpi-card kpi-card--primary">
                    <header><span>{{ __('Revenue') }}</span><i class="fa-solid fa-sack-dollar"></i></header>
                    <strong class="kpi-value">{{ $money($summary['revenue']) }}</strong>
                    <footer>{!! $delta($comparison['revenue'] ?? null) !!}<span class="kpi-vs">{{ __('vs prev.') }}</span></footer>
                </article>
                <article class="kpi-card">
                    <header><span>{{ __('COGS') }}</span><i class="fa-solid fa-receipt"></i></header>
                    <strong class="kpi-value">{{ $money($summary['cogs']) }}</strong>
                    <footer>{!! $delta($comparison['cogs'] ?? null) !!}<span class="kpi-vs">{{ __('vs prev.') }}</span></footer>
                </article>
                <article class="kpi-card">
                    <header><span>{{ __('Gross profit') }}</span><i class="fa-solid fa-arrow-trend-up"></i></header>
                    <strong class="kpi-value">{{ $money($summary['profit']) }}</strong>
                    <footer>{!! $delta($comparison['profit'] ?? null) !!}<span class="kpi-vs">{{ __('vs prev.') }}</span></footer>
                </article>
                <article class="kpi-card">
                    <header><span>{{ __('Margin') }}</span><i class="fa-solid fa-percent"></i></header>
                    <strong class="kpi-value">{{ number_format($summary['margin'], 1) }}%</strong>
                    <footer>{!! $delta($comparison['margin'] ?? null) !!}<span class="kpi-vs">{{ __('vs prev.') }}</span></footer>
                </article>
            </div>

            @if (! empty($chart))
                <section class="premium-card pr-chart">
                    <header class="pr-chart__head">
                        <h3>{{ __('Top products by revenue') }}</h3>
                        <p>{{ __('Highest earners in the selected period') }}</p>
                    </header>
                    <div class="pr-chart__canvas" data-pr-chart></div>
                </section>
            @endif

            <x-admin.table-card ajax :loader="false">
                <x-slot:toolbar>
                    <x-table-toolbar>
                        <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                        <x-slot:right><x-search-input name="search" placeholder="{{ __('Search product or SKU...') }}" /></x-slot:right>
                    </x-table-toolbar>
                </x-slot:toolbar>

                <table class="premium-table pr-table">
                    <thead>
                        <tr>
                            @php [$nameLink, $nameDir] = $sortLink('name'); @endphp
                            <th><a href="{{ $nameLink }}" class="th-sort {{ $nameDir ? 'is-'.$nameDir : '' }}">{{ __('Product') }}<i class="fa-solid fa-sort"></i></a></th>
                            @php [$skuLink, $skuDir] = $sortLink('sku'); @endphp
                            <th><a href="{{ $skuLink }}" class="th-sort {{ $skuDir ? 'is-'.$skuDir : '' }}">{{ __('SKU') }}<i class="fa-solid fa-sort"></i></a></th>
                            <th>{{ __('Stock') }}</th>
                            @php [$qtyLink, $qtyDir] = $sortLink('quantity'); @endphp
                            <th class="ta-r"><a href="{{ $qtyLink }}" class="th-sort {{ $qtyDir ? 'is-'.$qtyDir : '' }}">{{ __('Units') }}<i class="fa-solid fa-sort"></i></a></th>
                            @php [$revLink, $revDir] = $sortLink('revenue'); @endphp
                            <th class="ta-r"><a href="{{ $revLink }}" class="th-sort {{ $revDir ? 'is-'.$revDir : '' }}">{{ __('Revenue') }}<i class="fa-solid fa-sort"></i></a></th>
                            @php [$cogsLink, $cogsDir] = $sortLink('cogs'); @endphp
                            <th class="ta-r"><a href="{{ $cogsLink }}" class="th-sort {{ $cogsDir ? 'is-'.$cogsDir : '' }}">{{ __('COGS') }}<i class="fa-solid fa-sort"></i></a></th>
                            @php [$profitLink, $profitDir] = $sortLink('profit'); @endphp
                            <th class="ta-r"><a href="{{ $profitLink }}" class="th-sort {{ $profitDir ? 'is-'.$profitDir : '' }}">{{ __('Profit') }}<i class="fa-solid fa-sort"></i></a></th>
                            @php [$marginLink, $marginDir] = $sortLink('margin'); @endphp
                            <th class="ta-r"><a href="{{ $marginLink }}" class="th-sort {{ $marginDir ? 'is-'.$marginDir : '' }}">{{ __('Margin') }}<i class="fa-solid fa-sort"></i></a></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            @php $badge = $badgeLabel($badges[$product['name'].'|'.$product['sku']] ?? null); @endphp
                            <tr>
                                <td>
                                    <div class="pr-product-cell">
                                        <span class="pr-thumb">
                                            @if ($product['image'])
                                                <img src="{{ Imageurl($product['image'], 'products') }}" alt="">
                                            @else
                                                <i class="fa-solid fa-box-open"></i>
                                            @endif
                                        </span>
                                        <div>
                                            @if ($product['product_id'] && \Illuminate\Support\Facades\Route::has('admin.products.edit'))
                                                <a href="{{ route('admin.products.edit', $product['product_id']) }}" class="pr-product-link">{{ $product['name'] }}</a>
                                            @else
                                                <strong>{{ $product['name'] }}</strong>
                                            @endif
                                            @if ($badge)
                                                <span class="pr-badge pr-badge--{{ $badges[$product['name'].'|'.$product['sku']] }}">{{ $badge }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $product['sku'] ?: '—' }}</td>
                                <td>
                                    @if ($product['product_type'] === 'single' && $product['stock'] !== null)
                                        <span class="{{ $product['low_stock_alert'] !== null && $product['stock'] <= $product['low_stock_alert'] ? 'is-neg' : '' }}">{{ number_format($product['stock']) }}</span>
                                    @else
                                        <span class="is-muted">—</span>
                                    @endif
                                </td>
                                <td class="ta-r">{{ number_format($product['quantity']) }}</td>
                                <td class="ta-r">${{ number_format($product['revenue'], 2) }}</td>
                                <td class="ta-r">${{ number_format($product['cogs'], 2) }}</td>
                                <td class="ta-r">${{ number_format($product['profit'], 2) }}</td>
                                <td class="ta-r"><span class="pr-margin {{ $marginClass($product['margin']) }}">{{ number_format($product['margin'], 1) }}%</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <x-admin.empty-state icon="fa-solid fa-box-open" title="{{ __('No product sales') }}" message="{{ __('Paid order lines will appear here.') }}" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($products->count() > 0)
                        <tfoot>
                            <tr>
                                <td colspan="3">{{ __('Total (filtered)') }}</td>
                                <td class="ta-r">{{ number_format($tableTotals['units']) }}</td>
                                <td class="ta-r">${{ number_format($tableTotals['revenue'], 2) }}</td>
                                <td class="ta-r">${{ number_format($tableTotals['cogs'], 2) }}</td>
                                <td class="ta-r">${{ number_format($tableTotals['profit'], 2) }}</td>
                                <td class="ta-r">{{ number_format($tableTotals['margin'], 1) }}%</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>

                <x-slot:footer><x-table-footer :paginator="$products" label="{{ __('products') }}" /></x-slot:footer>
            </x-admin.table-card>
        </div>
    </x-admin.report-shell>

    @push('js')
    <script>
        (function () {
            let chart = null;

            const destroy = () => { if (chart) { try { chart.destroy(); } catch (e) {} chart = null; } };

            const boot = () => {
                destroy();
                const root = document.querySelector('[data-product-report]');
                const el = root ? root.querySelector('[data-pr-chart]') : null;
                if (!el || typeof ApexCharts === 'undefined') return;

                let ROWS = [];
                try { ROWS = JSON.parse(root.dataset.chart || '[]'); } catch (e) { ROWS = []; }
                if (!ROWS.length) return;

                chart = new ApexCharts(el, {
                    chart: { type: 'bar', height: Math.max(220, ROWS.length * 34), fontFamily: 'inherit', toolbar: { show: false },
                        animations: { enabled: true, easing: 'easeinout', speed: 450 } },
                    series: [{ name: '{{ __('Revenue') }}', data: ROWS.map(r => r.revenue) }],
                    colors: ['#0ea5e9'],
                    plotOptions: { bar: { horizontal: true, borderRadius: 3, borderRadiusApplication: 'end', barHeight: '55%' } },
                    dataLabels: { enabled: false },
                    grid: { borderColor: '#eef1f6', strokeDashArray: 4 },
                    xaxis: { categories: ROWS.map(r => r.name), labels: { style: { fontSize: '11px' }, formatter: (v) => '$' + Number(v).toLocaleString('en-US', { maximumFractionDigits: 0 }) } },
                    yaxis: { labels: { style: { fontSize: '11px' } } },
                    tooltip: { y: { formatter: (v) => '$' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2 }) } },
                });
                chart.render();
            };

            if (typeof ApexCharts !== 'undefined') boot(); else document.addEventListener('DOMContentLoaded', boot);
            document.addEventListener('ajax:page-unload', destroy);
            document.addEventListener('ajax:page-loaded', boot);
        })();
    </script>
    @endpush

    <style>
        .pr { display: flex; flex-direction: column; gap: 1.15rem; }

        /* KPI cards — same visual language as the Reports Overview page. */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.9rem; }
        .kpi-card { position: relative; overflow: hidden; background: #fff; border: 1px solid #e9edf4; border-radius: 16px; padding: 1.05rem 1.1rem 0;
            box-shadow: 0 1px 2px rgba(16,24,40,.04); display: flex; flex-direction: column; min-height: 128px; }
        .kpi-card--primary { background: linear-gradient(160deg, #0f172a 0%, #1e293b 100%); border-color: #0f172a; }
        .kpi-card--primary header span, .kpi-card--primary .kpi-vs { color: #94a3b8; }
        .kpi-card--primary .kpi-value { color: #fff; }
        .kpi-card--primary header i { background: rgba(255,255,255,.12); color: #e2e8f0; }
        .kpi-card header { display: flex; justify-content: space-between; align-items: center; }
        .kpi-card header span { font-size: 0.76rem; font-weight: 600; color: #64748b; }
        .kpi-card header i { width: 30px; height: 30px; display: grid; place-items: center; border-radius: 9px; background: #f1f5f9; color: #475569; font-size: 0.8rem; }
        .kpi-value { font-size: 1.7rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0.55rem 0 0.4rem; }
        .kpi-card footer { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; padding-bottom: 1rem; }
        .kpi-vs { font-size: 0.72rem; color: #94a3b8; }
        .kpi-delta { display: inline-flex; align-items: center; gap: 0.28rem; font-size: 0.76rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 999px; }
        .kpi-delta.is-up { color: #059669; background: #ecfdf5; }
        .kpi-delta.is-down { color: #e11d48; background: #fef2f2; }
        .kpi-delta.is-flat { color: #64748b; background: #f1f5f9; }
        html.dark .kpi-card { background: #101a2e; border-color: rgba(255,255,255,.08); }
        html.dark .kpi-value { color: #f1f5f9; }
        html.dark .kpi-card header i { background: rgba(255,255,255,.08); color: #cbd5e1; }

        .pr-kpi-grid { grid-template-columns: repeat(6, 1fr); }
        .pr-kpi-grid .kpi-card { min-height: 128px; }

        .pr-chart { padding: 1.1rem 1.25rem; }
        .pr-chart__head h3 { font-size: 0.98rem; font-weight: 650; color: #0f172a; margin: 0; }
        .pr-chart__head p { font-size: 0.78rem; color: #94a3b8; margin: 0.2rem 0 0; }
        .pr-chart__canvas { margin-top: 0.5rem; }

        .pr-product-cell { display: flex; align-items: center; gap: 0.65rem; }
        .pr-thumb { width: 34px; height: 34px; flex-shrink: 0; border-radius: 8px; overflow: hidden; background: #f1f5f9;
            display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 0.85rem; }
        .pr-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .pr-product-link { font-weight: 650; color: #0f172a; text-decoration: none; }
        .pr-product-link:hover { color: #0ea5e9; text-decoration: underline; }
        .pr-product-cell > div { display: flex; flex-direction: column; gap: 0.2rem; }

        .pr-badge { display: inline-block; width: fit-content; font-size: 0.62rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.03em; padding: 0.08rem 0.4rem; border-radius: 5px; }
        .pr-badge--best-seller { color: #047857; background: #ecfdf5; }
        .pr-badge--slow-mover { color: #92400e; background: #fffbeb; }

        .pr-margin { font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 6px; }
        .pr-margin.is-good { color: #059669; background: #ecfdf5; }
        .pr-margin.is-warn { color: #b45309; background: #fffbeb; }
        .pr-margin.is-danger { color: #e11d48; background: #fef2f2; }

        .pr-table tfoot td { position: sticky; bottom: 0; background: #0f172a; color: #fff; font-weight: 700; font-size: 0.82rem; }
        html.dark .pr-table tfoot td { background: #1b2840; }
        .pr-table .ta-r { text-align: right; }
        .pr-table .is-neg { color: #e11d48; font-weight: 650; }
        .pr-table .is-muted { color: #94a3b8; }
        .pr-table .th-sort { display: inline-flex; align-items: center; gap: 0.3rem; color: inherit; text-decoration: none; }
        .pr-table .th-sort i { font-size: 0.65rem; color: #cbd5e1; }
        .pr-table .th-sort.is-up i, .pr-table .th-sort.is-down i { color: #0f172a; }
        html.dark .pr-table .th-sort.is-up i, html.dark .pr-table .th-sort.is-down i { color: #f1f5f9; }

        @media (max-width: 1180px) {
            .pr-kpi-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 720px) {
            .pr-kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</x-app-layout>
