<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Analytics') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Sales Report') }}</h2>
        </div>
    </x-slot>

    <x-admin.report-shell
        active="sales"
        :title="__('Sales Report')"
        :filters="$filters"
        :action="route('admin.reports.sales')"
        :export-url="route('admin.reports.sales.export', request()->query())"
        :pdf-url="route('admin.reports.sales.export', ['format' => 'pdf'] + request()->query())"
        show-status
        show-payment
        :order-statuses="$orderStatuses"
        :payment-statuses="$paymentStatuses">

        <div class="finance-report-rail">
            <div>
                <span>{{ __('Gross sales') }}</span>
                <strong>${{ number_format($summary['gross_sales'], 2) }}</strong>
            </div>
            <div>
                <span>{{ __('Refunds') }}</span>
                <strong>${{ number_format($summary['refunds'], 2) }}</strong>
            </div>
            <div class="is-positive">
                <span>{{ __('Net sales') }}</span>
                <strong>${{ number_format($summary['net_sales'], 2) }}</strong>
            </div>
            <div>
                <span>{{ __('Average order') }}</span>
                <strong>${{ number_format($summary['average_order'], 2) }}</strong>
            </div>
        </div>

        <div class="finance-report-metrics">
            <div><span>{{ __('Orders') }}</span><strong>{{ number_format($summary['orders']) }}</strong></div>
            <div><span>{{ __('Paid orders') }}</span><strong>{{ number_format($summary['paid_orders']) }}</strong></div>
            <div><span>{{ __('Tax collected') }}</span><strong>${{ number_format($summary['tax_total'], 2) }}</strong></div>
            <div><span>{{ __('Shipping') }}</span><strong>${{ number_format($summary['shipping_total'], 2) }}</strong></div>
            <div><span>{{ __('Discounts') }}</span><strong>${{ number_format($summary['discount_total'], 2) }}</strong></div>
        </div>

        <x-admin.table-card ajax :loader="false">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search date (YYYY-MM-DD)...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table finance-report-table">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Orders') }}</th>
                        <th>{{ __('Gross') }}</th>
                        <th>{{ __('Tax') }}</th>
                        <th>{{ __('Shipping') }}</th>
                        <th>{{ __('Discounts') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($salesByDay as $day)
                        <tr>
                            <td><strong>{{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}</strong></td>
                            <td>{{ number_format($day['orders']) }}</td>
                            <td>${{ number_format($day['gross_sales'], 2) }}</td>
                            <td>${{ number_format($day['tax'], 2) }}</td>
                            <td>${{ number_format($day['shipping'], 2) }}</td>
                            <td>${{ number_format($day['discounts'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-admin.empty-state icon="fa-solid fa-chart-line" title="{{ __('No sales in range') }}" message="{{ __('Try a different date range or payment filter.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$salesByDay" label="{{ __('days') }}" /></x-slot:footer>
        </x-admin.table-card>
    </x-admin.report-shell>
</x-app-layout>
