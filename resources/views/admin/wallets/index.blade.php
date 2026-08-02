<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Sales</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Customer Wallets') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="cart-recovery-strip">
            <div class="cart-recovery-stat">
                <span><i class="fa-solid fa-users"></i> Customers</span>
                <strong>{{ number_format($customers->total()) }}</strong>
            </div>
            <div class="cart-recovery-stat cart-recovery-stat--active">
                <span><i class="fa-solid fa-wallet"></i> Total balance held</span>
                <strong>${{ number_format($totalBalance, 2) }}</strong>
            </div>
        </div>

        <div class="page-section-header">
            <div>
                <p class="section-kicker">Store credit</p>
                <h3>Customer Wallets</h3>
                <p class="text-gray-500">Credit or debit a customer's store-wallet balance. Every change is logged.</p>
            </div>
        </div>

        <x-admin.table-card>
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="Search name or email..." /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Balance</th>
                        <th style="width:420px">Adjust</th>
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
                                    <input type="text" name="note" placeholder="Note (optional)" class="form-input" style="width:150px">
                                    <button type="submit" name="direction" value="credit" class="premium-button premium-button--dark" style="padding:8px 12px"><i class="fa-solid fa-plus"></i> Credit</button>
                                    <button type="submit" name="direction" value="debit" class="ghost-button ghost-button--panel" style="padding:8px 12px"><i class="fa-solid fa-minus"></i> Debit</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <x-admin.empty-state icon="fa-solid fa-wallet" title="No customers found"
                                    message="Customer wallet balances will appear here." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$customers" label="customers" /></x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
