<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Sales') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Customer Wallets') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="cart-recovery-strip">
            <div class="cart-recovery-stat">
                <span><i class="fa-solid fa-users"></i> {{ __('Customers') }}</span>
                <strong>{{ number_format($customers->total()) }}</strong>
            </div>
            <div class="cart-recovery-stat cart-recovery-stat--active">
                <span><i class="fa-solid fa-wallet"></i> {{ __('Total balance held') }}</span>
                <strong>${{ number_format($totalBalance, 2) }}</strong>
            </div>
        </div>

        @if($pendingTopups->isNotEmpty())
            <div class="page-section-header">
                <div>
                    <p class="section-kicker">{{ __('Manual top-ups') }}</p>
                    <h3>{{ __('Pending top-up requests') }} <span class="badge bg-warning text-dark">{{ $pendingTopups->count() }}</span></h3>
                    <p class="text-gray-500">{{ __('Approve to credit the wallet, or reject if payment was not received.') }}</p>
                </div>
            </div>

            <x-admin.table-card class="mb-4">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th>{{ __('Requested') }}</th>
                            <th style="width:280px">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingTopups as $topup)
                            <tr>
                                <td>
                                    <strong>{{ $topup->user?->name ?? __('Unknown') }}</strong>
                                    <small class="d-block text-gray-400">{{ $topup->user?->email }}</small>
                                </td>
                                <td><strong style="font-size:16px">${{ number_format((float) $topup->amount, 2) }}</strong></td>
                                <td>{{ $topup->payment_method }}</td>
                                <td class="text-gray-500">{{ $topup->tran_id }}</td>
                                <td class="text-gray-500">{{ $topup->created_at?->format('M j, Y · g:i A') }}</td>
                                <td>
                                    <div class="d-flex" style="gap:8px;align-items:center;flex-wrap:wrap">
                                        <form method="POST" action="{{ route('admin.wallets.topups.approve', $topup) }}">
                                            @csrf
                                            <button type="submit" class="premium-button premium-button--dark" style="padding:8px 12px"><i class="fa-solid fa-check"></i> {{ __('Approve') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.wallets.topups.reject', $topup) }}" class="d-flex" style="gap:6px;align-items:center">
                                            @csrf
                                            <input type="text" name="note" placeholder="{{ __('Reason (optional)') }}" class="form-input" style="width:140px">
                                            <button type="submit" class="ghost-button ghost-button--panel" style="padding:8px 12px"><i class="fa-solid fa-xmark"></i> {{ __('Reject') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-admin.table-card>
        @endif

        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Store credit') }}</p>
                <h3>{{ __('Customer Wallets') }}</h3>
                <p class="text-gray-500">{{ __("Credit or debit a customer's store-wallet balance. Every change is logged.") }}</p>
            </div>
        </div>

        <x-admin.table-card>
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="{{ __('Search name or email...') }}" /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table">
                <thead>
                    <tr>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Balance') }}</th>
                        <th style="width:420px">{{ __('Adjust') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>
                                <strong>{{ $customer->name }}</strong>
                                <small class="d-block text-gray-400">{{ $customer->email }}</small>
                            </td>
                            <td><strong style="font-size:16px">${{ number_format((float) $customer->wallet_balance, 2) }}</strong></td>
                            <td>
                                <form method="POST" action="{{ route('admin.wallets.adjust', $customer) }}" class="d-flex" style="gap:8px;align-items:center;flex-wrap:wrap">
                                    @csrf
                                    <input type="number" name="amount" min="0.01" step="0.01" placeholder="0.00" required class="form-input" style="width:100px">
                                    <input type="text" name="note" placeholder="{{ __('Note (optional)') }}" class="form-input" style="width:150px">
                                    <button type="submit" name="direction" value="credit" class="premium-button premium-button--dark" style="padding:8px 12px"><i class="fa-solid fa-plus"></i> {{ __('Credit') }}</button>
                                    <button type="submit" name="direction" value="debit" class="ghost-button ghost-button--panel" style="padding:8px 12px"><i class="fa-solid fa-minus"></i> {{ __('Debit') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <x-admin.empty-state icon="fa-solid fa-wallet" title="{{ __('No customers found') }}"
                                    message="{{ __('Customer wallet balances will appear here.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$customers" label="{{ __('customers') }}" /></x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
