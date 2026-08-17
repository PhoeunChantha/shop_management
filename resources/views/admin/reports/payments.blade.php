<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Analytics') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Payment Report') }}</h2>
        </div>
    </x-slot>

    <x-admin.report-shell
        active="payments"
        :title="__('Payment Report')"
        :filters="$filters"
        :action="route('admin.reports.payments')"
        :export-url="route('admin.reports.payments.export', request()->query())"
        :pdf-url="route('admin.reports.payments.export', ['format' => 'pdf'] + request()->query())">

        <div class="finance-report-rail">
            <div>
                <span>{{ __('Orders') }}</span>
                <strong>{{ number_format($summary['orders']) }}</strong>
            </div>
            <div class="is-positive">
                <span>{{ __('Collected') }}</span>
                <strong>${{ number_format($summary['collected'], 2) }}</strong>
            </div>
            <div>
                <span>{{ __('Outstanding') }}</span>
                <strong>${{ number_format($summary['outstanding'], 2) }}</strong>
            </div>
        </div>

        <x-admin.table-card ajax :loader="false">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search payment status...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table finance-report-table">
                <thead>
                    <tr>
                        <th>{{ __('Payment status') }}</th>
                        <th>{{ __('Orders') }}</th>
                        <th>{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paymentMix as $payment)
                        <tr>
                            <td><strong>{{ $payment['label'] }}</strong></td>
                            <td>{{ number_format($payment['count']) }}</td>
                            <td>${{ number_format($payment['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <x-admin.empty-state icon="fa-solid fa-credit-card" title="{{ __('No payment data') }}" message="{{ __('Orders in this range will appear here.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$paymentMix" label="{{ __('statuses') }}" /></x-slot:footer>
        </x-admin.table-card>
    </x-admin.report-shell>
</x-app-layout>
