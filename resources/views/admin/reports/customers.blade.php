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

        <section class="finance-report-panel">
            <div class="finance-report-panel__head">
                <div>
                    <p class="section-kicker">{{ __('Customer Value') }}</p>
                    <h4>{{ __('Top customers by spend') }}</h4>
                </div>
            </div>
            <div class="table-responsive">
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
            </div>
        </section>
    </x-admin.report-shell>
</x-app-layout>
