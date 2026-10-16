<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Analytics') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Inventory Report') }}</h2>
        </div>
    </x-slot>

    <x-admin.report-shell
        active="stock"
        :title="__('Stock Report')"
        :action="route('admin.reports.stock')"
        :export-url="route('admin.reports.stock.export')"
        :pdf-url="route('admin.reports.stock.export', ['format' => 'pdf'])"
        :show-range="false">

        <div class="finance-report-metrics report-metrics">
            <div>
                <i class="report-metrics__icon fa-solid fa-barcode"></i>
                <span>{{ __('SKUs') }}</span><strong>{{ number_format($summary['skus']) }}</strong>
                <small>{{ __('Tracked items') }}</small>
            </div>
            <div>
                <i class="report-metrics__icon fa-solid fa-boxes-stacked"></i>
                <span>{{ __('Units on hand') }}</span><strong>{{ number_format($summary['units']) }}</strong>
                <small>{{ __('Across all SKUs') }}</small>
            </div>
            @can('view finance reports')
                <div>
                    <i class="report-metrics__icon fa-solid fa-sack-dollar"></i>
                    <span>{{ __('Stock value') }}</span><strong>${{ number_format($summary['value'], 2) }}</strong>
                    <small>{{ __('At unit cost') }}</small>
                </div>
            @endcan
            <div @class(['is-warn' => $summary['low'] > 0])>
                <i class="report-metrics__icon fa-solid fa-triangle-exclamation"></i>
                <span>{{ __('Low stock') }}</span><strong>{{ number_format($summary['low']) }}</strong>
                <small>{{ __('At or below threshold') }}</small>
            </div>
            <div @class(['is-danger' => $summary['out'] > 0])>
                <i class="report-metrics__icon fa-solid fa-ban"></i>
                <span>{{ __('Out of stock') }}</span><strong>{{ number_format($summary['out']) }}</strong>
                <small>{{ $summary['out'] > 0 ? __('Needs restock') : __('All in stock') }}</small>
            </div>
        </div>

        <x-admin.table-card ajax :loader="false">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search item or SKU...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table finance-report-table">
                <thead>
                    <tr>
                        <th>{{ __('Item') }}</th>
                        <th>{{ __('SKU') }}</th>
                        <th>{{ __('Stock') }}</th>
                        <th>{{ __('Threshold') }}</th>
                        @can('view finance reports')
                            <th>{{ __('Unit cost') }}</th>
                            <th>{{ __('Value') }}</th>
                        @endcan
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lowStock as $item)
                        <tr>
                            <td><strong>{{ $item['name'] }}</strong></td>
                            <td>{{ $item['sku'] ?: '—' }}</td>
                            <td>{{ number_format($item['stock']) }}</td>
                            <td>{{ number_format($item['threshold']) }}</td>
                            @can('view finance reports')
                                <td>${{ number_format($item['unit_cost'], 2) }}</td>
                                <td>${{ number_format($item['value'], 2) }}</td>
                            @endcan
                            <td>
                                <span class="status-pill {{ $item['severity'] === 'Out of stock' ? 'status-pill--danger' : 'status-pill--warning' }}">
                                    {{ $item['severity'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-admin.empty-state icon="fa-solid fa-warehouse" title="{{ __('Stock healthy') }}" message="{{ __('No items are at or below their reorder threshold.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$lowStock" label="{{ __('items') }}" /></x-slot:footer>
        </x-admin.table-card>
    </x-admin.report-shell>
</x-app-layout>
