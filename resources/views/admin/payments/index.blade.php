<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Sales') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Payments') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="cart-recovery-strip">
            <div class="cart-recovery-stat">
                <span><i class="fa-solid fa-receipt"></i> {{ __('Transactions') }}</span>
                <strong>{{ number_format($stats['total']) }}</strong>
            </div>
            <div class="cart-recovery-stat cart-recovery-stat--active">
                <span><i class="fa-solid fa-check-double"></i> {{ __('Completed') }}</span>
                <strong>{{ number_format($stats['completed']) }}</strong>
            </div>
            <div class="cart-recovery-stat">
                <span><i class="fa-solid fa-sack-dollar"></i> {{ __('Collected') }}</span>
                <strong>${{ number_format($stats['collected'], 2) }}</strong>
            </div>
        </div>

        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Gateway') }}</p>
                <h3>{{ __('Payment Transactions') }}</h3>
            </div>
            <a href="{{ route('admin.payments.export', request()->query()) }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-file-export"></i><span>{{ __('Export CSV') }}</span>
            </a>
        </div>

        <x-filter-card :action="route('admin.payments.index')">
            <x-select name="status" size="sm" :value="request('status')" placeholder="{{ __('Any status') }}"
                :options="['pending' => 'Pending', 'completed' => 'Completed', 'failed' => 'Failed']" />
        </x-filter-card>

        <x-admin.table-card>
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
                                <span class="status-chip {{ $chip }}">{{ ucfirst($payment->status) }}</span>
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
