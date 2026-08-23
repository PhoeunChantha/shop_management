<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Analytics') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Customer Report') }}</h2>
        </div>
    </x-slot>

    <x-admin.report-shell
        active="customers"
        :title="__('Customer Report')"
        :filters="$filters"
        :action="route('admin.reports.customers')"
        :export-url="route('admin.reports.customers.export', request()->query())"
        :pdf-url="route('admin.reports.customers.export', ['format' => 'pdf'] + request()->query())">

        <div class="finance-report-metrics">
            <div><span>{{ __('Paying customers') }}</span><strong>{{ number_format($summary['customers']) }}</strong></div>
            <div><span>{{ __('Repeat customers') }}</span><strong>{{ number_format($summary['repeat']) }}</strong></div>
            <div><span>{{ __('Revenue') }}</span><strong>${{ number_format($summary['revenue'], 2) }}</strong></div>
        </div>

        <x-admin.table-card ajax :loader="false">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search customer or email...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table finance-report-table">
                <thead>
                    <tr>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Orders') }}</th>
                        <th>{{ __('Spend') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td><strong>{{ $customer['customer_name'] ?: '—' }}</strong></td>
                            <td>{{ $customer['customer_email'] }}</td>
                            <td>{{ number_format($customer['orders']) }}</td>
                            <td>${{ number_format($customer['spend'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-admin.empty-state icon="fa-solid fa-users" title="{{ __('No customer spend') }}" message="{{ __('Paid customer orders will appear here.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$customers" label="{{ __('customers') }}" /></x-slot:footer>
        </x-admin.table-card>
    </x-admin.report-shell>
</x-app-layout>
