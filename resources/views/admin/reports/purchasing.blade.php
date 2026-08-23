<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Analytics') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Purchasing Report') }}</h2>
        </div>
    </x-slot>

    <x-admin.report-shell
        active="purchasing"
        :title="__('Purchasing Report')"
        :filters="$filters"
        :action="route('admin.reports.purchasing')"
        :export-url="route('admin.reports.purchasing.export', request()->query())"
        :pdf-url="route('admin.reports.purchasing.export', ['format' => 'pdf'] + request()->query())">

        <div class="finance-report-metrics">
            <div><span>{{ __('Purchase orders') }}</span><strong>{{ number_format($summary['orders']) }}</strong></div>
            <div><span>{{ __('Total cost') }}</span><strong>${{ number_format($summary['total_cost'], 2) }}</strong></div>
            <div><span>{{ __('Received') }}</span><strong>{{ number_format($summary['received']) }}</strong></div>
            <div><span>{{ __('Pending') }}</span><strong>{{ number_format($summary['pending']) }}</strong></div>
        </div>

        <section class="finance-report-panel">
            <div class="finance-report-panel__head">
                <div>
                    <p class="section-kicker">{{ __('Supplier Spend') }}</p>
                    <h4>{{ __('Cost by supplier') }}</h4>
                </div>
            </div>
            <div class="finance-report-list">
                @forelse ($supplierSpend as $supplier)
                    <div>
                        <div>
                            <strong>{{ $supplier['supplier'] }}</strong>
                            <span>{{ number_format($supplier['orders']) }} {{ __('orders') }}</span>
                        </div>
                        <b>${{ number_format($supplier['spend'], 2) }}</b>
                    </div>
                @empty
                    <x-admin.empty-state icon="fa-solid fa-truck-field" title="{{ __('No supplier spend') }}" message="{{ __('Purchase orders in this range will appear here.') }}" />
                @endforelse
            </div>
        </section>

        <x-admin.table-card ajax :loader="false">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search PO, supplier, status...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table finance-report-table">
                <thead>
                    <tr>
                        <th>{{ __('PO Number') }}</th>
                        <th>{{ __('Supplier') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Ordered') }}</th>
                        <th>{{ __('Subtotal') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchaseOrders as $purchase)
                        <tr>
                            <td><strong>{{ $purchase['po_number'] }}</strong></td>
                            <td>{{ $purchase['supplier'] }}</td>
                            <td>{{ $purchase['status'] }}</td>
                            <td>{{ $purchase['ordered_at'] }}</td>
                            <td>${{ number_format($purchase['subtotal'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-admin.empty-state icon="fa-solid fa-clipboard-list" title="{{ __('No purchase orders') }}" message="{{ __('Purchase orders in this range will appear here.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$purchaseOrders" label="{{ __('purchase orders') }}" /></x-slot:footer>
        </x-admin.table-card>
    </x-admin.report-shell>
</x-app-layout>
