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

        <x-slot:controls>
            <div class="report-controlbar__field">
                <span>{{ __('Status') }}</span>
                <x-select name="status" size="sm" :value="$filters['status'] ?? null" placeholder="{{ __('All statuses') }}" :options="$txStatuses" />
            </div>
            @if (!empty($methodOptions))
                <div class="report-controlbar__field">
                    <span>{{ __('Method') }}</span>
                    <x-select name="method" size="sm" :value="$filters['method'] ?? null" placeholder="{{ __('All methods') }}" :options="$methodOptions" />
                </div>
            @endif
        </x-slot:controls>

        <div class="finance-report-rail">
            <div class="is-positive">
                <span>{{ __('Captured') }}</span>
                <strong>${{ number_format($summary['captured_amount'], 2) }}</strong>
            </div>
            <div>
                <span>{{ __('Success rate') }}</span>
                <strong>{{ number_format($summary['success_rate'], 1) }}%</strong>
            </div>
            <div>
                <span>{{ __('Pending') }}</span>
                <strong>${{ number_format($summary['pending_amount'], 2) }}</strong>
            </div>
            <div>
                <span>{{ __('Transactions') }}</span>
                <strong>{{ number_format($summary['transactions']) }}</strong>
            </div>
        </div>

        <div class="finance-report-metrics">
            <div><span>{{ __('Captured payments') }}</span><strong>{{ number_format($summary['captured']) }}</strong></div>
            <div><span>{{ __('Pending payments') }}</span><strong>{{ number_format($summary['pending']) }}</strong></div>
            <div><span>{{ __('Failed payments') }}</span><strong>{{ number_format($summary['failed']) }}</strong></div>
        </div>

        @if ($byMethod->isNotEmpty())
            <section class="finance-report-panel" style="margin-bottom:16px">
                <div class="finance-report-panel__head">
                    <div>
                        <p class="section-kicker">{{ __('Distribution') }}</p>
                        <h4>{{ __('Captured by method') }}</h4>
                    </div>
                </div>
                <div class="finance-report-list">
                    @foreach ($byMethod as $method)
                        <div>
                            <div>
                                <strong>{{ $method['method'] }}</strong>
                                <span>{{ number_format($method['count']) }} {{ __('transactions') }}</span>
                            </div>
                            <b>${{ number_format($method['captured'], 2) }}</b>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <x-admin.table-card ajax :loader="false">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search transaction, order, method...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table finance-report-table">
                <thead>
                    <tr>
                        <th>{{ __('Transaction') }}</th>
                        <th>{{ __('Order') }}</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('Gateway') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr>
                            <td><strong>{{ $tx['tran_id'] }}</strong></td>
                            <td>{{ $tx['order'] }}</td>
                            <td>{{ $tx['method'] }}</td>
                            <td>{{ $tx['gateway'] }}</td>
                            <td>${{ number_format($tx['amount'], 2) }}</td>
                            <td>
                                <span class="status-pill {{ $tx['status'] === 'Completed' ? '' : ($tx['status'] === 'Failed' ? 'status-pill--danger' : 'status-pill--warning') }}">
                                    {{ $tx['status'] }}
                                </span>
                            </td>
                            <td>{{ $tx['date'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-admin.empty-state icon="fa-solid fa-credit-card" title="{{ __('No transactions') }}" message="{{ __('Gateway payments in this range will appear here.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$transactions" label="{{ __('transactions') }}" /></x-slot:footer>
        </x-admin.table-card>
    </x-admin.report-shell>
</x-app-layout>
