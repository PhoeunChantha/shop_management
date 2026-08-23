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

        <div class="finance-report-metrics">
            <div><span>{{ __('Products sold') }}</span><strong>{{ number_format($summary['products']) }}</strong></div>
            <div><span>{{ __('Units sold') }}</span><strong>{{ number_format($summary['units']) }}</strong></div>
            <div><span>{{ __('Revenue') }}</span><strong>${{ number_format($summary['revenue'], 2) }}</strong></div>
            <div><span>{{ __('COGS') }}</span><strong>${{ number_format($summary['cogs'], 2) }}</strong></div>
            <div><span>{{ __('Gross profit') }}</span><strong>${{ number_format($summary['profit'], 2) }}</strong></div>
            <div><span>{{ __('Margin') }}</span><strong>{{ number_format($summary['margin'], 1) }}%</strong></div>
        </div>

        <x-admin.table-card ajax :loader="false">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search product or SKU...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table finance-report-table">
                <thead>
                    <tr>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('SKU') }}</th>
                        <th>{{ __('Units') }}</th>
                        <th>{{ __('Revenue') }}</th>
                        <th>{{ __('COGS') }}</th>
                        <th>{{ __('Profit') }}</th>
                        <th>{{ __('Margin') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td><strong>{{ $product['name'] }}</strong></td>
                            <td>{{ $product['sku'] ?: '—' }}</td>
                            <td>{{ number_format($product['quantity']) }}</td>
                            <td>${{ number_format($product['revenue'], 2) }}</td>
                            <td>${{ number_format($product['cogs'], 2) }}</td>
                            <td>${{ number_format($product['profit'], 2) }}</td>
                            <td>{{ number_format($product['margin'], 1) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-admin.empty-state icon="fa-solid fa-box-open" title="{{ __('No product sales') }}" message="{{ __('Paid order lines will appear here.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$products" label="{{ __('products') }}" /></x-slot:footer>
        </x-admin.table-card>
    </x-admin.report-shell>
</x-app-layout>
