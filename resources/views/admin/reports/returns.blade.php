<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Analytics') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Return Report') }}</h2>
        </div>
    </x-slot>

    <x-admin.report-shell
        active="returns"
        :title="__('Return Report')"
        :filters="$filters"
        :action="route('admin.reports.returns')"
        :export-url="route('admin.reports.returns.export', request()->query())"
        :pdf-url="route('admin.reports.returns.export', ['format' => 'pdf'] + request()->query())"
        show-status
        :order-statuses="$returnStatuses">

        <div class="finance-report-metrics">
            <div><span>{{ __('Total returns') }}</span><strong>{{ number_format($summary['total']) }}</strong></div>
            <div><span>{{ __('Refunded') }}</span><strong>{{ number_format($summary['refunded']) }}</strong></div>
            <div><span>{{ __('Refund amount') }}</span><strong>${{ number_format($summary['refund_amount'], 2) }}</strong></div>
            <div><span>{{ __('Pending') }}</span><strong>{{ number_format($summary['pending']) }}</strong></div>
        </div>

        @if ($byStatus->isNotEmpty())
            <div class="finance-report-rail">
                @foreach ($byStatus as $status)
                    <div>
                        <span>{{ $status['label'] }}</span>
                        <strong>{{ number_format($status['count']) }}</strong>
                    </div>
                @endforeach
            </div>
        @endif

        <x-admin.table-card ajax :loader="false">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search return #, order, customer...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table finance-report-table">
                <thead>
                    <tr>
                        <th>{{ __('Return #') }}</th>
                        <th>{{ __('Order') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Reason') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Refund') }}</th>
                        <th>{{ __('Refunded') }}</th>
                        <th>{{ __('Requested') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($returns as $return)
                        <tr>
                            <td><strong>{{ $return['return_number'] }}</strong></td>
                            <td>{{ $return['order'] }}</td>
                            <td>{{ $return['customer'] }}</td>
                            <td>{{ $return['reason'] }}</td>
                            <td>{{ $return['status'] }}</td>
                            <td>{{ $return['refund_status'] }}</td>
                            <td>${{ number_format($return['refund_amount'], 2) }}</td>
                            <td>{{ $return['requested_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-admin.empty-state icon="fa-solid fa-rotate-left" title="{{ __('No returns') }}" message="{{ __('Return requests in this range will appear here.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$returns" label="{{ __('returns') }}" /></x-slot:footer>
        </x-admin.table-card>
    </x-admin.report-shell>
</x-app-layout>
