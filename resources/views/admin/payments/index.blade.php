<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Sales') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Payments') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Gateway') }}</p>
                <h3>{{ __('Payment Transactions') }}</h3>
            </div>
            <a href="{{ route('admin.payments.export', request()->query()) }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-file-export"></i><span>{{ __('Export CSV') }}</span>
            </a>
        </div>

        {{-- KPI stat bar (follows the active filters) --}}
        @php($cards = [
            ['label' => __('Collected'), 'value' => money($stats['collected']), 'raw' => $stats['collected'], 'decimals' => 2, 'icon' => 'fa-sack-dollar', 'tone' => 'emerald'],
            ['label' => __('Transactions'), 'value' => number_format($stats['total']), 'raw' => $stats['total'], 'icon' => 'fa-receipt', 'tone' => 'blue'],
            ['label' => __('Completed'), 'value' => number_format($stats['completed']), 'raw' => $stats['completed'], 'icon' => 'fa-circle-check', 'tone' => 'violet'],
            ['label' => __('Pending'), 'value' => number_format($stats['pending']), 'raw' => $stats['pending'], 'icon' => 'fa-clock', 'tone' => 'amber'],
            ['label' => __('Failed'), 'value' => number_format($stats['failed']), 'raw' => $stats['failed'], 'icon' => 'fa-circle-xmark', 'tone' => 'rose'],
        ])
        <x-admin.stat-cards :cards="$cards" />

        {{-- Filters --}}
        <x-filter-card :action="route('admin.payments.index')" :grid="'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3'">
            <x-slot:hidden>
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            </x-slot:hidden>

            <x-select name="status" size="sm" :label="__('Status')" :value="request('status')" :placeholder="__('Any status')"
                :options="$statuses" />

            <x-select name="gateway" size="sm" :label="__('Gateway')" :value="request('gateway')" :placeholder="__('Any gateway')"
                :options="$filterOptions['gateways']" />

            <x-select name="payment_option" size="sm" :label="__('Method')" :value="request('payment_option')" :placeholder="__('Any method')"
                :options="$filterOptions['options']" searchable />

            <x-select name="currency" size="sm" :label="__('Currency')" :value="request('currency')" :placeholder="__('Any currency')"
                :options="$filterOptions['currencies']" />

            <x-select name="amount" size="sm" :label="__('Amount')" :value="request('amount')" :placeholder="__('Any amount')"
                :options="$amountRanges" />

            <div class="form-field">
                <label>{{ __('Date range') }}</label>
                <div class="daterange-control">
                    <i class="fa-regular fa-calendar"></i>
                    <input type="text" class="form-input" data-daterange placeholder="{{ __('Any date') }}"
                        readonly autocomplete="off"
                        value="{{ request('date_from') && request('date_to') ? \Illuminate\Support\Carbon::parse(request('date_from'))->format('M d, Y') . ' – ' . \Illuminate\Support\Carbon::parse(request('date_to'))->format('M d, Y') : '' }}">
                </div>
                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ request('date_to') }}">
            </div>
        </x-filter-card>

        <x-admin.table-card class="mt-3">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search order # / transaction / email...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table">
                <thead>
                    <tr>
                        <th>{{ __('Order') }}</th>
                        <th>{{ __('Gateway') }}</th>
                        <th>{{ __('Transaction') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>
                                @if($payment->order)
                                    <a href="{{ route('admin.orders.show', $payment->order) }}"><strong>{{ $payment->order->order_number }}</strong></a>
                                    <small class="d-block text-gray-400">{{ $payment->order->customer_email ?: $payment->order->customer_name }}</small>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="count-pill">{{ strtoupper($payment->gateway) }}</span>
                                @if($payment->payment_option)<small class="d-block text-gray-400 mt-1">{{ $payment->payment_option }}</small>@endif
                            </td>
                            <td><code>{{ $payment->tran_id }}</code></td>
                            <td><strong>{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</strong></td>
                            <td>
                                @php($chip = ['completed' => 'is-success', 'failed' => 'is-danger', 'pending' => 'is-warning'][$payment->status] ?? '')
                                <span class="status-chip {{ $chip }}">{{ __($statuses[$payment->status] ?? ucfirst($payment->status)) }}</span>
                            </td>
                            <td>
                                {{ $payment->created_at?->format('M d, Y g:i A') }}
                                <small class="d-block text-gray-400">{{ $payment->created_at?->diffForHumans() }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-admin.empty-state icon="fa-solid fa-credit-card" title="{{ __('No payments yet') }}"
                                    message="{{ __('Gateway transactions (ABA PayWay) will appear here once customers pay online.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$payments" label="{{ __('payments') }}" /></x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
