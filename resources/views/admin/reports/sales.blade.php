@php
    $money = fn ($v) => '$'.number_format((float) $v, 2);
    $hasData = $summary['orders'] > 0;
    $query = $filters->toQuery();
    $finance = auth()->user()->can('view finance reports');
    $prev = $filters->previous();

    $sortLink = function (string $key) {
        $asc = request('sort') === $key && request('direction') === 'asc';
        return [
            request()->fullUrlWithQuery(['sort' => $key, 'direction' => $asc ? 'desc' : 'asc', 'page' => null]),
            request('sort') === $key ? (request('direction') === 'asc' ? 'up' : 'down') : null,
        ];
    };
    $th = function (string $key, string $label, bool $right = true) use ($sortLink) {
        [$link, $dir] = $sortLink($key);
        return '<th'.($right ? ' class="ta-r"' : '').'><a href="'.e($link).'" class="th-sort '.($dir ? 'is-'.$dir : '').'">'.e($label).'<i class="fa-solid fa-sort"></i></a></th>';
    };
    $titles = [
        'summary' => __('Sales overview'),
        'products' => __('Sales by product'),
        'categories' => __('Sales by category'),
        'customers' => __('Sales by customer'),
        'methods' => __('Sales by payment method'),
        'orders' => __('Sales transactions'),
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Analytics') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Sales') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page sales-report" data-sales-report>
        <x-admin.report-tabs :tabs="\App\Services\Admin\Reports\ReportNavigation::tabs('sales')" />

        {{-- ===================== INTRO / ACTIONS ===================== --}}
        <div class="sr-intro">
            <p class="sr-intro__sub">{{ __('Paid orders placed in the period. Refunds use the shared sales definition: recorded refunds first, implied only for orders marked refunded.') }}</p>
            <div class="sr-intro__actions">
                <div class="export-menu" x-data="{ open: false }" @keydown.escape="open = false">
                    <button type="button" class="premium-button premium-button--dark" @click="open = !open" :aria-expanded="open">
                        <i class="fa-solid fa-file-export"></i><span>{{ __('Export') }}</span>
                        <i class="fa-solid fa-chevron-down export-menu__caret"></i>
                    </button>
                    <div class="export-menu__panel" x-show="open" x-transition.origin.top.right @click.outside="open = false" x-cloak>
                        <a href="{{ route('admin.reports.sales.export', ['format' => 'csv'] + request()->except('page')) }}">
                            <i class="fa-solid fa-file-csv"></i>{{ __('Export CSV') }}
                        </a>
                        <a href="{{ route('admin.reports.sales.export', ['format' => 'pdf'] + request()->except('page')) }}">
                            <i class="fa-solid fa-file-pdf"></i>{{ __('Export PDF') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================== FILTERS ===================== --}}
        <form method="GET" action="{{ route('admin.reports.sales') }}" class="sr-filters" data-ajax-filter>
            <input type="hidden" name="view" value="{{ $view }}">
            <div class="sr-filters__field sr-filters__field--range">
                <span>{{ __('Date range') }}</span>
                <div class="daterange-control">
                    <i class="fa-solid fa-calendar-days"></i>
                    <input type="text" class="form-input" data-daterange data-daterange-from="start_date" data-daterange-to="end_date" placeholder="{{ __('Select report range') }}" readonly>
                    <input type="hidden" name="start_date" value="{{ $filters->start->toDateString() }}">
                    <input type="hidden" name="end_date" value="{{ $filters->end->toDateString() }}">
                </div>
            </div>

            {{-- Server-side customer autocomplete (never loads the full base). --}}
            <div class="sr-filters__field sr-filters__field--customer"
                 x-data="salesCustomerFilter('{{ route('admin.reports.sales.customers') }}', @js($filters->get('customer', '')))">
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

            <div class="sr-filters__field">
                <span>{{ __('Payment method') }}</span>
                <x-select name="payment_method" size="sm" :value="$filters->get('payment_method')" placeholder="{{ __('All methods') }}" :options="$paymentMethods" />
            </div>

            <div class="sr-filters__field">
                <span>{{ __('Order status') }}</span>
                <x-select name="status" size="sm" :value="$filters->get('status')" placeholder="{{ __('All statuses') }}" :options="$orderStatuses" />
            </div>

            <div class="sr-filters__field">
                <span>{{ __('Payment status') }}</span>
                <x-select name="payment_status" size="sm" :value="$filters->get('payment_status')" placeholder="{{ __('All captured') }}" :options="$paymentStatuses" />
            </div>

            <div class="sr-filters__actions">
                <a href="{{ route('admin.reports.sales', ['view' => $view]) }}" class="ghost-button" data-ajax-link>
                    <i class="fa-solid fa-rotate-left"></i><span>{{ __('Reset') }}</span>
                </a>
                <button type="submit" class="filter-button">
                    <i class="fa-solid fa-filter"></i><span>{{ __('Apply') }}</span>
                </button>
            </div>
        </form>

        <p class="rpt-period mb-3">
            <i class="fa-regular fa-calendar"></i>
            <strong>{{ $filters->start->format('M d, Y') }} – {{ $filters->end->format('M d, Y') }}</strong>
            <span class="rpt-period__prev">{{ __('compared with') }} {{ $prev->start->format('M d') }} – {{ $prev->end->format('M d, Y') }}</span>
            @if ($unpaidOrders > 0)
                · <span>{{ trans_choice('{1} :count unpaid order excluded|[2,*] :count unpaid orders excluded', $unpaidOrders, ['count' => $unpaidOrders]) }}</span>
            @endif
        </p>

        @if (! $hasData)
            <div class="rpt-empty">
                <i class="fa-solid fa-receipt"></i>
                <h3>{{ __('No paid sales for this period') }}</h3>
                <p>{{ __('Try changing your date range or filters.') }}</p>
            </div>
        @else

        {{-- ===================== HEADLINE KPIs (every tab) ===================== --}}
        <div class="rpt">
            <div class="kpi-grid">
                <article class="kpi-card kpi-card--primary">
                    <header><span>{{ __('Total sales') }}</span><i class="fa-solid fa-sack-dollar"></i></header>
                    <strong class="kpi-value">{{ $money($summary['total_sales']) }}</strong>
                    <footer><x-admin.report-delta :data="$comparison['total_sales']" /><span class="kpi-vs">{{ __('incl. tax & shipping') }}</span></footer>
                </article>
                <article class="kpi-card">
                    <header><span>{{ __('Net sales') }}</span><i class="fa-solid fa-scale-balanced"></i></header>
                    <strong class="kpi-value">{{ $money($summary['net_sales']) }}</strong>
                    <footer><x-admin.report-delta :data="$comparison['net_sales']" /><span class="kpi-vs">{{ __('gross − discounts − refunds') }}</span></footer>
                </article>
                <article class="kpi-card">
                    <header><span>{{ __('Paid orders') }}</span><i class="fa-solid fa-bag-shopping"></i></header>
                    <strong class="kpi-value">{{ number_format($summary['orders']) }}</strong>
                    <footer><x-admin.report-delta :data="$comparison['orders']" /><span class="kpi-vs">{{ number_format($summary['units']) }} {{ __('units sold') }}</span></footer>
                </article>
                <article class="kpi-card">
                    <header><span>{{ __('Average order value') }}</span><i class="fa-solid fa-receipt"></i></header>
                    <strong class="kpi-value">{{ $money($summary['average_order_value']) }}</strong>
                    <footer><x-admin.report-delta :data="$comparison['average_order_value']" /><span class="kpi-vs">{{ __('(gross − discounts) ÷ orders') }}</span></footer>
                </article>
            </div>

            <div class="rpt-metrics">
                <div class="rpt-metric"><span>{{ __('Gross sales') }}</span><strong>{{ $money($summary['gross_sales']) }}</strong><small><x-admin.report-delta :data="$comparison['gross_sales']" /></small></div>
                <div class="rpt-metric"><span>{{ __('Discounts') }}</span><strong>{{ $money($summary['discounts']) }}</strong><small><x-admin.report-delta :data="$comparison['discounts']" inverse /></small></div>
                <div class="rpt-metric">
                    <span>{{ __('Refunds') }}</span>
                    <strong class="{{ $summary['refunds'] > 0 ? 'rpt-neg' : '' }}">{{ $money($summary['refunds']) }}</strong>
                    <small><x-admin.report-delta :data="$comparison['refunds']" inverse />{{ __('recorded') }} {{ $money($summary['recorded_refunds']) }} · {{ __('implied') }} {{ $money($summary['implied_refunds']) }}</small>
                </div>
                <div class="rpt-metric"><span>{{ __('Units sold') }}</span><strong>{{ number_format($summary['units']) }}</strong><small><x-admin.report-delta :data="$comparison['units']" />{{ __('before returns') }}</small></div>
            </div>
        </div>

        @if ($view === 'summary')
            <div class="rpt-grid mt-3">
                <section class="rpt-panel">
                    <header class="rpt-panel__head">
                        <div>
                            <h3>{{ __('Net sales over time') }}</h3>
                            <p>{{ count($series) > 0 && strlen($series[0]['key']) === 7 ? __('Per month') : __('Per day') }}</p>
                        </div>
                    </header>
                    @php($chartRows = collect($series)->map(fn ($r) => ['x' => $r['label'], 'y' => $r['net_sales'], 'orders' => $r['orders']])->values())
                    <div class="sr-chart__canvas" data-sales-chart data-rows='@json($chartRows)' role="img" aria-label="{{ __('Net sales over time') }}"></div>
                </section>

                <section class="rpt-panel">
                    <header class="rpt-panel__head"><div><h3>{{ __('Sales waterfall') }}</h3><p>{{ __('How gross becomes total') }}</p></div></header>
                    <dl class="rpt-ledger">
                        <div><dt>{{ __('Gross sales') }}</dt><dd>{{ $money($summary['gross_sales']) }}</dd></div>
                        <div class="is-minus"><dt>{{ __('Discounts') }}</dt><dd>− {{ $money($summary['discounts']) }}</dd></div>
                        <div class="is-minus"><dt>{{ __('Product refunds') }}<small>{{ __('recorded') }} {{ $money($summary['recorded_refunds']) }} · {{ __('implied') }} {{ $money($summary['implied_refunds']) }}</small></dt><dd>− {{ $money($summary['merchandise_refunds']) }}</dd></div>
                        <div class="is-subtotal"><dt>{{ __('Net sales') }}</dt><dd>{{ $money($summary['net_sales']) }}</dd></div>
                        <div><dt>{{ __('Tax') }}</dt><dd>+ {{ $money($summary['tax']) }}</dd></div>
                        <div><dt>{{ __('Shipping') }}</dt><dd>+ {{ $money($summary['shipping']) }}</dd></div>
                        @if ($summary['tax_shipping_refunds'] > 0)
                            <div class="is-minus"><dt>{{ __('Tax & shipping refunded') }}</dt><dd>− {{ $money($summary['tax_shipping_refunds']) }}</dd></div>
                        @endif
                        <div class="is-total"><dt>{{ __('Total sales') }}</dt><dd>{{ $money($summary['total_sales']) }}</dd></div>
                    </dl>
                </section>
            </div>

            <section class="premium-card admin-table-card sr-daily mt-3">
                <div class="panel-head">
                    <h3>{{ __('Sales by date') }}</h3>
                    <span>{{ __('Paid orders only') }}</span>
                </div>
                <div class="premium-table-wrap admin-table-card__scroll sr-daily__scroll">
                    <table class="premium-table sr-table">
                        <thead>
                            <tr>
                                <th>{{ __('Period') }}</th>
                                <th class="ta-r">{{ __('Orders') }}</th>
                                <th class="ta-r">{{ __('Units') }}</th>
                                <th class="ta-r">{{ __('Gross sales') }}</th>
                                <th class="ta-r">{{ __('Discounts') }}</th>
                                <th class="ta-r">{{ __('Refunds') }}</th>
                                <th class="ta-r">{{ __('Net sales') }}</th>
                                <th class="ta-r">{{ __('Tax') }}</th>
                                <th class="ta-r">{{ __('Shipping') }}</th>
                                <th class="ta-r">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($series as $row)
                                <tr class="{{ $row['orders'] === 0 ? 'is-quiet' : '' }}">
                                    <td class="sr-date">{{ $row['long_label'] }}</td>
                                    <td class="ta-r">{{ number_format($row['orders']) }}</td>
                                    <td class="ta-r">{{ number_format($row['units']) }}</td>
                                    <td class="ta-r">{{ $money($row['gross_sales']) }}</td>
                                    <td class="ta-r {{ $row['discounts'] > 0 ? 'is-neg' : '' }}">{{ $row['discounts'] > 0 ? '− '.$money($row['discounts']) : '—' }}</td>
                                    <td class="ta-r {{ $row['refunds'] > 0 ? 'is-neg' : '' }}">{{ $row['refunds'] > 0 ? '− '.$money($row['refunds']) : '—' }}</td>
                                    <td class="ta-r"><strong>{{ $money($row['net_sales']) }}</strong></td>
                                    <td class="ta-r">{{ $money($row['tax']) }}</td>
                                    <td class="ta-r">{{ $money($row['shipping']) }}</td>
                                    <td class="ta-r"><strong>{{ $money($row['total_sales']) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>{{ __('Total') }}</td>
                                <td class="ta-r">{{ number_format($summary['orders']) }}</td>
                                <td class="ta-r">{{ number_format($summary['units']) }}</td>
                                <td class="ta-r">{{ $money($summary['gross_sales']) }}</td>
                                <td class="ta-r">{{ $summary['discounts'] > 0 ? '− '.$money($summary['discounts']) : '—' }}</td>
                                <td class="ta-r">{{ $summary['refunds'] > 0 ? '− '.$money($summary['refunds']) : '—' }}</td>
                                <td class="ta-r">{{ $money($summary['net_sales']) }}</td>
                                <td class="ta-r">{{ $money($summary['tax']) }}</td>
                                <td class="ta-r">{{ $money($summary['shipping']) }}</td>
                                <td class="ta-r">{{ $money($summary['total_sales']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        @elseif ($view === 'methods')
            @php($methodTotal = max(0.01, collect($rows)->sum('total_sales')))
            <section class="premium-card admin-table-card mt-3">
                <div class="panel-head">
                    <h3>{{ $titles[$view] }}</h3>
                    <span>{{ __('Method recorded on the order') }}</span>
                </div>
                <div class="premium-table-wrap admin-table-card__scroll">
                    <table class="premium-table sr-table">
                        <thead>
                            <tr>
                                <th>{{ __('Payment method') }}</th>
                                <th class="ta-r">{{ __('Orders') }}</th>
                                <th class="ta-r">{{ __('Gross sales') }}</th>
                                <th class="ta-r">{{ __('Discounts') }}</th>
                                <th class="ta-r">{{ __('Refunds') }}</th>
                                <th class="ta-r">{{ __('Net sales') }}</th>
                                <th class="ta-r">{{ __('Total sales') }}</th>
                                <th class="ta-r">{{ __('Share') }}</th>
                                <th class="ta-r">{{ __('Avg. order') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td><strong>{{ $row['method'] }}</strong>@if ($row['code'])<small class="d-block rpt-muted">{{ $row['code'] }}</small>@endif</td>
                                    <td class="ta-r">{{ number_format($row['orders']) }}</td>
                                    <td class="ta-r">{{ $money($row['gross']) }}</td>
                                    <td class="ta-r {{ $row['discounts'] > 0 ? 'is-neg' : 'is-muted' }}">{{ $row['discounts'] > 0 ? '− '.$money($row['discounts']) : '—' }}</td>
                                    <td class="ta-r {{ $row['refunds'] > 0 ? 'is-neg' : 'is-muted' }}">{{ $row['refunds'] > 0 ? '− '.$money($row['refunds']) : '—' }}</td>
                                    <td class="ta-r">{{ $money($row['net_sales']) }}</td>
                                    <td class="ta-r"><strong>{{ $money($row['total_sales']) }}</strong></td>
                                    <td class="ta-r">{{ number_format($row['total_sales'] / $methodTotal * 100, 1) }}%</td>
                                    <td class="ta-r">{{ $money($row['average_order_value']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @else
            {{-- Paginated breakdown tables --}}
            <section class="premium-card admin-table-card sr-orders mt-3" data-ajax-table>
                <x-table-toolbar>
                    <x-slot:left>
                        <div class="panel-head panel-head--flush">
                            <h3>{{ $titles[$view] }}</h3>
                            <span>
                                @if (in_array($view, ['products', 'categories'], true))
                                    {{ __('Discounts and refunds are allocated to lines by their share of the order.') }}
                                @elseif ($view === 'customers')
                                    {{ __('Grouped by email, so guest checkouts are included.') }}
                                @else
                                    {{ __('Paid orders with their refund breakdown.') }}
                                @endif
                            </span>
                        </div>
                        <x-per-page-selector :current="$filters->perPage()" />
                    </x-slot:left>
                    @if ($view !== 'categories')
                        <x-slot:right>
                            <x-search-input name="search" placeholder="{{ $view === 'products' ? __('Search product or SKU…') : __('Search order or customer…') }}" />
                        </x-slot:right>
                    @endif
                </x-table-toolbar>

                <div data-ajax-region>
                    <div class="premium-table-wrap admin-table-card__scroll">
                        <table class="premium-table sr-table sr-orders-table">
                            @if (in_array($view, ['products', 'categories'], true))
                                <thead>
                                    <tr>
                                        @if ($view === 'products')
                                            {!! $th('name', __('Product'), false) !!}
                                        @else
                                            <th>{{ __('Category') }}</th>
                                        @endif
                                        {!! $th('orders', __('Orders')) !!}
                                        {!! $th('quantity', __('Units')) !!}
                                        {!! $th('gross', __('Gross sales')) !!}
                                        <th class="ta-r">{{ __('Discounts') }}</th>
                                        {!! $th('refunds', __('Refunds')) !!}
                                        {!! $th('net', __('Net sales')) !!}
                                        @if ($finance)
                                            <th class="ta-r">{{ __('Cost') }}</th>
                                            {!! $th('profit', __('Profit')) !!}
                                            <th class="ta-r">{{ __('Margin') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $row)
                                        <tr>
                                            <td>
                                                <strong>{{ $row['name'] }}</strong>
                                                @if (! empty($row['sku']))<small class="d-block rpt-muted">{{ $row['sku'] }}</small>@endif
                                            </td>
                                            <td class="ta-r">{{ number_format($row['orders']) }}</td>
                                            <td class="ta-r">{{ number_format($row['quantity']) }}</td>
                                            <td class="ta-r">{{ $money($row['gross']) }}</td>
                                            <td class="ta-r {{ $row['discounts'] > 0 ? 'is-neg' : 'is-muted' }}">{{ $row['discounts'] > 0 ? '− '.$money($row['discounts']) : '—' }}</td>
                                            <td class="ta-r {{ $row['refunds'] > 0 ? 'is-neg' : 'is-muted' }}">{{ $row['refunds'] > 0 ? '− '.$money($row['refunds']) : '—' }}</td>
                                            <td class="ta-r"><strong>{{ $money($row['net_sales']) }}</strong></td>
                                            @if ($finance)
                                                <td class="ta-r" @if ($row['uncosted_units'] > 0) title="{{ trans_choice('{1} :count unit has no cost recorded|[2,*] :count units have no cost recorded', $row['uncosted_units'], ['count' => $row['uncosted_units']]) }}" @endif>
                                                    {{ $money($row['cogs']) }}@if ($row['uncosted_units'] > 0) <i class="fa-solid fa-circle-exclamation rpt-muted"></i>@endif
                                                </td>
                                                <td class="ta-r">{{ $money($row['profit']) }}</td>
                                                <td class="ta-r">{{ $row['margin'] !== null ? number_format($row['margin'], 1).'%' : '—' }}</td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr><td colspan="{{ $finance ? 10 : 7 }}"><x-admin.empty-state icon="fa-solid fa-shirt" title="{{ __('No sales found') }}" message="{{ __('Try a different date range or search.') }}" /></td></tr>
                                    @endforelse
                                </tbody>
                            @elseif ($view === 'customers')
                                <thead>
                                    <tr>
                                        {!! $th('name', __('Customer'), false) !!}
                                        {!! $th('orders', __('Orders')) !!}
                                        {!! $th('gross', __('Gross sales')) !!}
                                        <th class="ta-r">{{ __('Discounts') }}</th>
                                        {!! $th('refunds', __('Refunds')) !!}
                                        {!! $th('net', __('Net sales')) !!}
                                        {!! $th('total', __('Total sales')) !!}
                                        <th class="ta-r">{{ __('Avg. order') }}</th>
                                        {!! $th('last', __('Last order')) !!}
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $row)
                                        <tr>
                                            <td>
                                                <div class="cust-cell">
                                                    <strong>{{ $row['customer_name'] }}@if ($row['is_guest'])<span class="guest-tag">{{ __('Guest') }}</span>@endif</strong>
                                                    <small>{{ $row['customer_email'] }}</small>
                                                </div>
                                            </td>
                                            <td class="ta-r">{{ number_format($row['orders']) }}</td>
                                            <td class="ta-r">{{ $money($row['gross']) }}</td>
                                            <td class="ta-r {{ $row['discounts'] > 0 ? 'is-neg' : 'is-muted' }}">{{ $row['discounts'] > 0 ? '− '.$money($row['discounts']) : '—' }}</td>
                                            <td class="ta-r {{ $row['refunds'] > 0 ? 'is-neg' : 'is-muted' }}">{{ $row['refunds'] > 0 ? '− '.$money($row['refunds']) : '—' }}</td>
                                            <td class="ta-r">{{ $money($row['net_sales']) }}</td>
                                            <td class="ta-r"><strong>{{ $money($row['total_sales']) }}</strong></td>
                                            <td class="ta-r">{{ $money($row['average_order_value']) }}</td>
                                            <td class="ta-r">{{ $row['last_order_at'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9"><x-admin.empty-state icon="fa-solid fa-user" title="{{ __('No customers found') }}" message="{{ __('Try a different date range or search.') }}" /></td></tr>
                                    @endforelse
                                </tbody>
                            @else
                                <thead>
                                    <tr>
                                        {!! $th('date', __('Date'), false) !!}
                                        <th>{{ __('Order') }}</th>
                                        <th>{{ __('Customer') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Method') }}</th>
                                        <th class="ta-r">{{ __('Units') }}</th>
                                        <th class="ta-r">{{ __('Discount') }}</th>
                                        {!! $th('refunds', __('Refund')) !!}
                                        {!! $th('net', __('Net sales')) !!}
                                        {!! $th('total', __('Total')) !!}
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $tx)
                                        <tr>
                                            <td class="sr-date">{{ $tx['date'] }}<small>{{ $tx['time'] }}</small></td>
                                            <td><a href="{{ route('admin.orders.show', $tx['order_id']) }}" class="tx-order">#{{ $tx['order_number'] }}</a></td>
                                            <td>
                                                <div class="cust-cell">
                                                    <strong>{{ $tx['customer_name'] }}@if ($tx['is_guest'])<span class="guest-tag">{{ __('Guest') }}</span>@endif</strong>
                                                    @if ($tx['customer_email'])<small>{{ $tx['customer_email'] }}</small>@endif
                                                </div>
                                            </td>
                                            <td>
                                                @if ($tx['status'])<span class="status-chip {{ $tx['status']->badge() }}">{{ $tx['status']->label() }}</span>@endif
                                                @if ($tx['payment_status'])<span class="status-chip {{ $tx['payment_status']->badge() }}">{{ $tx['payment_status']->label() }}</span>@endif
                                            </td>
                                            <td>{{ $tx['method'] }}</td>
                                            <td class="ta-r">{{ number_format($tx['units']) }}</td>
                                            <td class="ta-r {{ $tx['discount'] > 0 ? 'is-neg' : 'is-muted' }}">{{ $tx['discount'] > 0 ? '− '.$money($tx['discount']) : '—' }}</td>
                                            <td class="ta-r {{ $tx['refund'] > 0 ? 'is-neg' : 'is-muted' }}"
                                                @if ($tx['refund'] > 0) title="{{ __('Recorded') }} {{ $money($tx['recorded_refund']) }} · {{ __('Implied') }} {{ $money($tx['implied_refund']) }}" @endif>
                                                {{ $tx['refund'] > 0 ? '− '.$money($tx['refund']) : '—' }}
                                                @if ($tx['implied_refund'] > 0)<small class="d-block rpt-muted">{{ __('implied') }}</small>@endif
                                            </td>
                                            <td class="ta-r">{{ $money($tx['net_sales']) }}</td>
                                            <td class="ta-r"><strong>{{ $money($tx['total']) }}</strong></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="10"><x-admin.empty-state icon="fa-solid fa-receipt" title="{{ __('No sales found') }}" message="{{ __('Try a different date range, customer, or status filter.') }}" /></td></tr>
                                    @endforelse
                                </tbody>
                            @endif
                        </table>
                    </div>
                    <x-table-footer :paginator="$rows" label="{{ __('rows') }}" />
                </div>
            </section>
        @endif
        @endif
    </div>

    @push('js')
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
            let chart = null;
            const destroy = () => { if (chart) { try { chart.destroy(); } catch (e) {} chart = null; } };

            const boot = () => {
                destroy();
                const root = document.querySelector('[data-sales-report]');
                const el = root ? root.querySelector('[data-sales-chart]') : null;
                if (!el || typeof ApexCharts === 'undefined') return;

                let ROWS = [];
                try { ROWS = JSON.parse(el.dataset.rows || '[]'); } catch (e) { ROWS = []; }

                const css = getComputedStyle(document.documentElement);
                const v = (name, fallback) => (css.getPropertyValue(name) || fallback).trim();
                const DARK = document.documentElement.classList.contains('dark');
                const money0 = (n) => '$' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 0 });
                const money2 = (n) => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                chart = new ApexCharts(el, {
                    chart: { type: 'bar', height: 260, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false },
                        animations: { enabled: false }, background: 'transparent' },
                    series: [{ name: @json(__('Net sales')), data: ROWS.map(r => r.y) }],
                    colors: [v('--viz-1', '#2a78d6')],
                    plotOptions: { bar: { columnWidth: ROWS.length > 60 ? '90%' : '60%', borderRadius: 4, borderRadiusApplication: 'end' } },
                    dataLabels: { enabled: false },
                    states: { hover: { filter: { type: 'darken', value: 0.9 } } },
                    grid: { borderColor: v('--sx-line', '#ebedf1'), strokeDashArray: 4, padding: { left: 6, right: 6 } },
                    xaxis: { categories: ROWS.map(r => r.x), tickAmount: Math.min(12, ROWS.length),
                        labels: { style: { colors: v('--sx-muted', '#79838f'), fontSize: '11px' }, rotate: 0, hideOverlappingLabels: true },
                        axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: { labels: { style: { colors: v('--sx-muted', '#79838f'), fontSize: '11px' }, formatter: money0 } },
                    tooltip: { theme: DARK ? 'dark' : 'light',
                        y: { formatter: (n, { dataPointIndex }) => money2(n) + ' · ' + (ROWS[dataPointIndex]?.orders ?? 0) + ' ' + @json(__('orders')) } },
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
        [x-cloak] { display: none !important; }
        .sales-report {
            --sr-ink: var(--admin-ink, #101827);
            --sr-muted: var(--admin-muted, #667085);
            --sr-soft: color-mix(in srgb, var(--admin-muted, #667085) 55%, transparent);
            --sr-line: var(--admin-line, #e4e7ec);
            --sr-panel: var(--admin-panel, #ffffff);
            --sr-hover: color-mix(in srgb, var(--admin-ink, #101827) 5%, var(--admin-panel, #fff));
            --sr-accent: var(--admin-accent, #0f766e);
            --sr-neg: #e11d48;
            display: flex; flex-direction: column; gap: 1.15rem;
        }
        html.dark .sales-report { --sr-neg: #fb7185; }
        .sales-report .premium-table td, .sales-report .sr-ledger dd, .sales-report .sr-stat strong, .sales-report .sr-summary__total strong { font-variant-numeric: tabular-nums; }

        /* Intro */
        .sr-intro { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; }
        .sr-intro__sub { font-size: 0.85rem; color: var(--sr-muted); margin: 0; }
        .sr-intro__actions { display: flex; gap: 0.55rem; flex-shrink: 0; margin-left: auto; }

        .export-menu { position: relative; }
        .export-menu__caret { font-size: 0.65rem; margin-left: 0.15rem; }
        .export-menu__panel { position: absolute; right: 0; top: calc(100% + 6px); z-index: 40; min-width: 180px;
            background: var(--sr-panel); border: 1px solid var(--sr-line); border-radius: 12px; box-shadow: var(--admin-shadow, 0 18px 40px -18px rgba(15,23,42,.35)); padding: 6px; }
        .export-menu__panel a { display: flex; align-items: center; gap: 0.6rem; padding: 0.55rem 0.7rem; border-radius: 8px;
            font-size: 0.83rem; color: var(--sr-ink); text-decoration: none; }
        .export-menu__panel a:hover { background: var(--sr-hover); }
        .export-menu__panel i { width: 16px; color: var(--sr-muted); }

        /* Filters */
        .sr-filters { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.8rem;
            background: var(--sr-panel); border: 1px solid var(--sr-line); border-radius: 16px; padding: 0.95rem 1.05rem; box-shadow: 0 1px 2px rgba(16,24,40,.03); }
        .sr-filters__field { display: flex; flex-direction: column; gap: 0.35rem; min-width: 170px; }
        .sr-filters__field > span { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--sr-muted); }
        .sr-filters__field--range { min-width: 240px; }
        .sr-filters__field--customer { min-width: 230px; }
        .sr-filters__actions { display: flex; gap: 0.5rem; margin-left: auto; }
        .daterange-control { position: relative; display: flex; align-items: center; }
        .daterange-control > i { position: absolute; left: 0.7rem; color: var(--sr-muted); font-size: 0.8rem; pointer-events: none; }
        .daterange-control .form-input { padding-left: 2rem; cursor: pointer; }

        .customer-select { position: relative; }
        .customer-select__control { position: relative; display: flex; align-items: center; }
        .customer-select__control > i { position: absolute; left: 0.7rem; color: var(--sr-muted); font-size: 0.8rem; }
        .customer-select__control .form-input { padding-left: 2rem; padding-right: 2rem; width: 100%; }
        .customer-select__clear { position: absolute; right: 0.55rem; border: none; background: transparent; color: var(--sr-muted); cursor: pointer; }
        .customer-select__clear:hover { color: var(--sr-neg); }
        .customer-select__menu { position: absolute; z-index: 45; top: calc(100% + 5px); left: 0; right: 0; max-height: 260px; overflow-y: auto;
            background: var(--sr-panel); border: 1px solid var(--sr-line); border-radius: 12px; box-shadow: var(--admin-shadow, 0 20px 44px -20px rgba(15,23,42,.4)); padding: 5px; }
        .customer-select__opt { display: flex; flex-direction: column; gap: 1px; width: 100%; text-align: left; border: none; background: transparent;
            padding: 0.5rem 0.6rem; border-radius: 8px; cursor: pointer; }
        .customer-select__opt:hover { background: var(--sr-hover); }
        .customer-select__name { font-size: 0.83rem; font-weight: 600; color: var(--sr-ink); }
        .customer-select__mail { font-size: 0.72rem; color: var(--sr-muted); }
        .customer-select__hint { padding: 0.6rem; font-size: 0.78rem; color: var(--sr-muted); text-align: center; }

        /* Summary ledger */
        .sr-summary { padding: 0; overflow: hidden; }
        .sr-summary__head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1.5rem; flex-wrap: wrap;
            padding: 1.25rem 1.4rem 1.1rem; background: linear-gradient(160deg, #0f172a 0%, #1e293b 100%); color: #fff; }
        html.dark .sr-summary__head { background: linear-gradient(160deg, #0b1220 0%, #17233a 100%); border-bottom: 1px solid var(--sr-line); }
        .sr-eyebrow { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #94a3b8; margin: 0 0 0.35rem; }
        .sr-summary__period { font-size: 1.3rem; font-weight: 650; letter-spacing: -0.01em; margin: 0; color: #fff; }
        .sr-summary__period span { color: #64748b; margin: 0 0.35rem; }
        .sr-summary__meta { font-size: 0.78rem; color: #94a3b8; margin: 0.4rem 0 0; display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; }
        .sr-chip { display: inline-flex; align-items: center; gap: 0.35rem; background: rgba(255,255,255,.1); color: #e2e8f0;
            padding: 0.15rem 0.55rem; border-radius: 999px; font-weight: 600; font-size: 0.72rem; }
        .sr-summary__total { text-align: right; }
        .sr-summary__total span { display: block; font-size: 0.72rem; font-weight: 600; color: #94a3b8; letter-spacing: 0.04em; text-transform: uppercase; }
        .sr-summary__total strong { display: block; font-size: 2.2rem; font-weight: 700; letter-spacing: -0.03em; line-height: 1.1; margin: 0.2rem 0; color: #fff; }
        .sr-summary__total small { font-size: 0.72rem; color: #94a3b8; }

        .sr-summary__body { display: grid; grid-template-columns: 1.25fr 1fr; }
        .sr-stats { display: grid; grid-template-columns: repeat(2, 1fr); border-right: 1px solid var(--sr-line); }
        .sr-stat { padding: 1.05rem 1.4rem; border-bottom: 1px solid var(--sr-line); }
        .sr-stat:nth-child(odd) { border-right: 1px solid var(--sr-line); }
        .sr-stat:nth-last-child(-n+2) { border-bottom: none; }
        .sr-stat span { display: block; font-size: 0.72rem; font-weight: 600; color: var(--sr-muted); text-transform: uppercase; letter-spacing: 0.04em; }
        .sr-stat strong { display: block; font-size: 1.45rem; font-weight: 700; color: var(--sr-ink); letter-spacing: -0.02em; margin: 0.3rem 0 0.15rem; }
        .sr-stat strong.is-neg { color: var(--sr-neg); }
        .sr-stat small { font-size: 0.72rem; color: var(--sr-muted); }

        .sr-ledger { margin: 0; padding: 0.6rem 1.4rem 0.7rem; display: flex; flex-direction: column; }
        .sr-ledger__row { display: flex; justify-content: space-between; align-items: baseline; padding: 0.5rem 0; font-size: 0.86rem; border-bottom: 1px dashed var(--sr-line); }
        .sr-ledger__row:last-child { border-bottom: none; }
        .sr-ledger__row dt { color: var(--sr-muted); margin: 0; font-weight: 500; }
        .sr-ledger__row dd { color: var(--sr-ink); margin: 0; font-weight: 600; }
        .sr-ledger__row.is-minus dd { color: var(--sr-neg); }
        .sr-ledger__row.is-plus dd { color: var(--sr-muted); font-weight: 500; }
        .sr-ledger__row.is-subtotal { border-top: 1px solid var(--sr-muted); }
        .sr-ledger__row.is-subtotal dt, .sr-ledger__row.is-subtotal dd { color: var(--sr-ink); font-weight: 650; }
        .sr-ledger__row.is-total { border-top: 2px solid var(--sr-ink); margin-top: 0.2rem; padding-top: 0.65rem; }
        .sr-ledger__row.is-total dt { color: var(--sr-ink); font-weight: 700; }
        .sr-ledger__row.is-total dd { color: var(--sr-ink); font-weight: 700; font-size: 1.05rem; }

        /* Chart */
        .sr-chart { padding: 1.15rem 1.25rem 0.6rem; }
        .sr-chart__head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
        .sr-chart__head h3 { font-size: 0.98rem; font-weight: 650; color: var(--sr-ink); margin: 0; }
        .sr-chart__head p { font-size: 0.78rem; color: var(--sr-muted); margin: 0.2rem 0 0; }
        .sr-legend { display: flex; gap: 1rem; font-size: 0.76rem; color: var(--sr-muted); font-weight: 600; }
        .sr-legend span { display: inline-flex; align-items: center; gap: 0.4rem; }
        .sr-legend__swatch { width: 10px; height: 10px; border-radius: 3px; background: var(--sr-accent); display: inline-block; }
        .sr-chart__canvas { margin-top: 0.4rem; }

        /* Tables */
        .panel-head { display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; padding: 1.05rem 1.2rem 0.75rem; }
        .panel-head--flush { padding: 0; }
        .panel-head h3 { font-size: 0.95rem; font-weight: 650; color: var(--sr-ink); margin: 0; }
        .panel-head span { font-size: 0.74rem; color: var(--sr-muted); }
        .sr-daily, .sr-orders { padding: 0; }
        .sr-orders .table-toolbar { padding: 1.05rem 1.2rem; }
        .sr-daily__scroll { max-height: 520px; overflow-y: auto; }
        .sr-table th, .sr-table td { padding: 0.6rem 0.85rem; white-space: nowrap; }
        .sr-table td { font-size: 0.82rem; color: var(--sr-ink); }
        .sr-table tbody tr.is-quiet td { color: var(--sr-soft); }
        .sr-table tbody tr.is-quiet td strong { color: var(--sr-soft); font-weight: 500; }
        .sr-table td.sr-date { color: var(--sr-muted); font-size: 0.8rem; }
        .sr-table td.sr-date small { display: block; font-size: 0.68rem; color: var(--sr-soft); }
        .sr-daily thead th { position: sticky; top: 0; z-index: 2; }
        .sr-table tfoot td { position: sticky; bottom: 0; z-index: 2; background: #0f172a; color: #fff; font-weight: 700; font-size: 0.82rem; border-top: 2px solid #0f172a; }
        html.dark .sr-table tfoot td { background: #1b2840; border-top-color: var(--sr-accent); }
        .sr-table tfoot td:first-child { text-transform: uppercase; letter-spacing: 0.06em; font-size: 0.7rem; }
        .ta-r { text-align: right; }
        .is-neg { color: var(--sr-neg); }
        .is-muted { color: var(--sr-soft); }
        .cust-cell strong { display: block; font-size: 0.82rem; color: var(--sr-ink); font-weight: 600; }
        .cust-cell small { font-size: 0.72rem; color: var(--sr-muted); }
        .guest-tag { display: inline-block; font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;
            color: #a16207; background: #fef9c3; padding: 0.05rem 0.35rem; border-radius: 5px; margin-left: 0.35rem; vertical-align: middle; }
        .tx-order { font-weight: 650; color: #4f46e5; text-decoration: none; font-size: 0.82rem; }
        html.dark .tx-order { color: #a5b4fc; }
        .tx-order:hover { text-decoration: underline; }
        .th-sort { display: inline-flex; align-items: center; gap: 0.3rem; color: inherit; text-decoration: none; }
        .th-sort i { font-size: 0.65rem; color: var(--sr-soft); }
        .th-sort.is-up i, .th-sort.is-down i { color: var(--sr-ink); }
        .status-chip { display: inline-block; font-size: 0.7rem; font-weight: 650; padding: 0.18rem 0.5rem; border-radius: 6px; }

        /* Empty */
        .sr-empty { text-align: center; padding: 3rem 1.5rem; display: flex; flex-direction: column; align-items: center; gap: 0.6rem; }
        .sr-empty i { font-size: 2rem; color: var(--sr-soft); }
        .sr-empty h3 { font-size: 1.05rem; font-weight: 650; color: var(--sr-ink); margin: 0.3rem 0 0; }
        .sr-empty p { font-size: 0.85rem; color: var(--sr-muted); margin: 0 0 0.6rem; }

        @media (max-width: 1100px) {
            .sr-summary__body { grid-template-columns: 1fr; }
            .sr-stats { border-right: none; border-bottom: 1px solid var(--sr-line); }
        }
        @media (max-width: 720px) {
            .sr-intro { flex-direction: column; align-items: flex-start; }
            .sr-filters__actions { margin-left: 0; width: 100%; }
            .sr-summary__head { align-items: flex-start; }
            .sr-summary__total { text-align: left; }
            .sr-stats { grid-template-columns: 1fr; }
            .sr-stat:nth-child(odd) { border-right: none; }
            .sr-stat:nth-last-child(-n+2) { border-bottom: 1px solid var(--sr-line); }
            .sr-stat:last-child { border-bottom: none; }
        }
    </style>
</x-app-layout>
