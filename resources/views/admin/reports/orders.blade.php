@php
    $money = fn ($v) => '$'.number_format((float) $v, 2);
    $query = $filters->toQuery();
    $hours = function (?float $h) {
        if ($h === null) {
            return '—';
        }
        return $h < 48 ? number_format($h, 1).' '.__('h') : number_format($h / 24, 1).' '.__('days');
    };
    $sortLink = function (string $key) {
        $asc = request('sort') === $key && request('direction') === 'asc';
        return [
            request()->fullUrlWithQuery(['sort' => $key, 'direction' => $asc ? 'desc' : 'asc', 'page' => null]),
            request('sort') === $key ? (request('direction') === 'asc' ? 'up' : 'down') : null,
        ];
    };
    $th = function (string $key, string $label, bool $right = false) use ($sortLink) {
        [$link, $dir] = $sortLink($key);
        return '<th'.($right ? ' class="ta-r"' : '').'><a href="'.e($link).'" class="th-sort '.($dir ? 'is-'.$dir : '').'">'.e($label).'<i class="fa-solid fa-sort"></i></a></th>';
    };
    $statusUrl = fn (string $status) => route('admin.reports.orders', ['view' => 'orders', 'status' => $status] + collect($query)->except(['view', 'status'])->all());
    $kpiIcons = ['total' => 'fa-bag-shopping', 'pending' => 'fa-hourglass-half', 'processing' => 'fa-gears', 'shipped' => 'fa-truck-fast', 'delivered' => 'fa-circle-check', 'cancelled' => 'fa-ban'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Analytics') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Orders & Fulfillment') }}</h2>
        </div>
    </x-slot>

    <x-admin.report-shell
        active="orders"
        :tabs="\App\Services\Admin\Reports\ReportNavigation::tabs('orders')"
        :filters="$query"
        :action="route('admin.reports.orders')"
        :export-url="route('admin.reports.orders.export', request()->except('page'))"
        :pdf-url="route('admin.reports.orders.export', ['format' => 'pdf'] + request()->except('page'))"
        show-status
        show-payment
        :order-statuses="$orderStatuses"
        :payment-statuses="$paymentStatuses">
        <x-slot:controls>
            <input type="hidden" name="view" value="{{ $view }}">
            <div class="report-controlbar__field">
                <span>{{ __('Fulfillment') }}</span>
                <x-select name="fulfillment_status" size="sm" :value="$filters->get('fulfillment_status')" placeholder="{{ __('Any fulfillment') }}" :options="$fulfillmentStatuses" />
            </div>
            <div class="report-controlbar__field">
                <span>{{ __('Payment method') }}</span>
                <x-select name="payment_method" size="sm" :value="$filters->get('payment_method')" placeholder="{{ __('All methods') }}" :options="$paymentMethods" />
            </div>
        </x-slot:controls>

        <div class="rpt">
            <p class="rpt-period">
                <i class="fa-regular fa-calendar"></i>
                <strong>{{ $filters->start->format('M d, Y') }} – {{ $filters->end->format('M d, Y') }}</strong>
                <span class="rpt-period__prev">{{ __('Every order placed in the period, paid or not. Revenue lives in Sales.') }}</span>
            </p>

            {{-- Status KPIs (real OrderStatus cases) --}}
            <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
                @foreach ($kpis as $key => $kpi)
                    @php($tag = $kpi['status'] ? 'a' : 'article')
                    <{{ $tag }} class="kpi-card {{ $key === 'total' ? 'kpi-card--primary' : '' }}" @if ($kpi['status']) href="{{ $statusUrl($kpi['status']) }}" style="text-decoration: none;" @endif>
                        <header><span>{{ $kpi['label'] }}</span><i class="fa-solid {{ $kpiIcons[$key] ?? 'fa-circle' }}"></i></header>
                        <strong class="kpi-value">{{ number_format($kpi['value']) }}</strong>
                        <footer><x-admin.report-delta :data="$kpi['comparison']" :inverse="$key === 'cancelled'" /></footer>
                    </{{ $tag }}>
                @endforeach
            </div>

            @if ($view === 'summary')
                <div class="rpt-grid rpt-grid--three">
                    <section class="rpt-panel">
                        <header class="rpt-panel__head"><div><h3>{{ __('Order status') }}</h3><p>{{ __('Share of orders placed in the period') }}</p></div></header>
                        <div class="rpt-status">
                            @foreach ($statuses as $row)
                                <a href="{{ $statusUrl($row['status']) }}">
                                    <span class="rpt-status__dot" style="background: {{ $row['color'] }}"></span>
                                    <span class="rpt-status__label">{{ $row['label'] }}<small class="d-block rpt-muted">{{ $money($row['value']) }}</small></span>
                                    <span class="rpt-status__count">{{ number_format($row['count']) }}</span>
                                    <span class="rpt-status__share">{{ $row['share'] !== null ? number_format($row['share'], 1).'%' : '—' }}</span>
                                </a>
                            @endforeach
                        </div>
                    </section>

                    <section class="rpt-panel">
                        <header class="rpt-panel__head"><div><h3>{{ __('Fulfillment') }}</h3><p>{{ __('Placed → shipped / fulfilled') }}</p></div></header>
                        <div class="rpt-status mb-3">
                            @foreach ($fulfillment as $row)
                                <div>
                                    <span class="rpt-status__dot" style="background: var(--sx-muted)"></span>
                                    <span class="rpt-status__label">{{ $row['label'] }}</span>
                                    <span class="rpt-status__count">{{ number_format($row['count']) }}</span>
                                    <span class="rpt-status__share">{{ $row['share'] !== null ? number_format($row['share'], 1).'%' : '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                        <dl class="rpt-ledger">
                            <div>
                                <dt>{{ __('Avg. time to ship') }}<small>{{ trans_choice('{0} no shipped orders|{1} from :count shipped order|[2,*] from :count shipped orders', $timing['ship']['orders'], ['count' => $timing['ship']['orders']]) }}</small></dt>
                                <dd>{{ $hours($timing['ship']['avg_hours']) }}</dd>
                            </div>
                            <div><dt>{{ __('Shipped within 48 h') }}</dt><dd>{{ $timing['ship']['within_48h'] !== null ? number_format($timing['ship']['within_48h'], 1).'%' : '—' }}</dd></div>
                            <div>
                                <dt>{{ __('Avg. time to fulfill') }}<small>{{ trans_choice('{0} no fulfilled orders|{1} from :count fulfilled order|[2,*] from :count fulfilled orders', $timing['fulfil']['orders'], ['count' => $timing['fulfil']['orders']]) }}</small></dt>
                                <dd>{{ $hours($timing['fulfil']['avg_hours']) }}</dd>
                            </div>
                        </dl>
                        <p class="rpt-note mt-3"><i class="fa-solid fa-circle-info"></i>{{ __('Delivery time is not reported: orders do not record when they were delivered.') }}</p>
                    </section>

                    <div class="rpt">
                        <section class="rpt-panel">
                            <header class="rpt-panel__head"><div><h3>{{ __('Open backlog') }}</h3><p>{{ __('Not yet fulfilled, all dates, by age') }}</p></div></header>
                            <div class="rpt-status">
                                @foreach ($backlog['buckets'] as $bucket)
                                    <div>
                                        <span class="rpt-status__dot" style="background: var(--viz-1)"></span>
                                        <span class="rpt-status__label">{{ $bucket['label'] }}</span>
                                        <span class="rpt-status__count">{{ number_format($bucket['count']) }}</span>
                                        <span class="rpt-status__share"></span>
                                    </div>
                                @endforeach
                            </div>
                            @can('view orders')
                                <p class="mt-2 mb-0" style="font-size: 12px;"><a href="{{ route('admin.orders.index', ['fulfillment_status' => 'unfulfilled']) }}">{{ __('Open the order queue') }} <i class="fa-solid fa-arrow-right"></i></a></p>
                            @endcan
                        </section>

                        <section class="rpt-panel">
                            <header class="rpt-panel__head"><div><h3>{{ __('Cancellations') }}</h3><p>{{ $cancellations['rate'] !== null ? number_format($cancellations['rate'], 1).'% '.__('of orders') : __('No orders') }}</p></div></header>
                            <dl class="rpt-ledger">
                                <div><dt>{{ __('Before payment') }}</dt><dd>{{ number_format($cancellations['before_payment']) }}</dd></div>
                                <div>
                                    <dt>{{ __('After payment') }}<small>{{ __('captured payment, check refunds') }}</small></dt>
                                    <dd>{{ number_format($cancellations['after_payment']) }} · {{ $money($cancellations['after_payment_value']) }}</dd>
                                </div>
                            </dl>
                        </section>
                    </div>
                </div>
            @else
                <section class="premium-card admin-table-card" data-ajax-table>
                    <x-table-toolbar>
                        <x-slot:left>
                            <div class="panel-head panel-head--flush">
                                <h3>{{ __('Order list') }}</h3>
                                <span>{{ __('Every order placed in the period') }}</span>
                            </div>
                            <x-per-page-selector :current="$filters->perPage()" />
                        </x-slot:left>
                        <x-slot:right>
                            <x-search-input name="search" placeholder="{{ __('Search order or customer…') }}" />
                        </x-slot:right>
                    </x-table-toolbar>

                    <div data-ajax-region>
                        <div class="premium-table-wrap admin-table-card__scroll">
                            <table class="premium-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Order') }}</th>
                                        {!! $th('date', __('Placed')) !!}
                                        <th>{{ __('Customer') }}</th>
                                        {!! $th('status', __('Status')) !!}
                                        <th>{{ __('Payment') }}</th>
                                        <th>{{ __('Fulfillment') }}</th>
                                        <th>{{ __('Shipped') }}</th>
                                        {!! $th('ship', __('Time to ship'), true) !!}
                                        {!! $th('total', __('Total'), true) !!}
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $row)
                                        <tr>
                                            <td><a href="{{ route('admin.orders.show', $row['order_id']) }}" class="tx-order">#{{ $row['order_number'] }}</a></td>
                                            <td>{{ $row['placed'] }}</td>
                                            <td><strong>{{ $row['customer_name'] }}</strong><small class="d-block rpt-muted">{{ $row['customer_email'] }}</small></td>
                                            <td>@if ($row['status'])<span class="status-chip {{ $row['status']->badge() }}">{{ $row['status']->label() }}</span>@endif</td>
                                            <td>@if ($row['payment_status'])<span class="status-chip {{ $row['payment_status']->badge() }}">{{ $row['payment_status']->label() }}</span>@endif<small class="d-block rpt-muted">{{ $row['method'] }}</small></td>
                                            <td>{{ $row['fulfillment_status']?->label() ?? '—' }}</td>
                                            <td>{{ $row['shipped'] ?? '—' }}@if ($row['carrier'])<small class="d-block rpt-muted">{{ $row['carrier'] }}</small>@endif</td>
                                            <td class="ta-r">{{ $hours($row['ship_hours']) }}</td>
                                            <td class="ta-r"><strong>{{ $money($row['total']) }}</strong></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9"><x-admin.empty-state icon="fa-solid fa-truck-fast" title="{{ __('No orders found') }}" message="{{ __('Try a different date range or filter.') }}" /></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <x-table-footer :paginator="$rows" label="{{ __('orders') }}" />
                    </div>
                </section>
            @endif
        </div>
    </x-admin.report-shell>
</x-app-layout>
