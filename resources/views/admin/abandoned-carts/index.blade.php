<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Sales recovery</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Abandoned Carts') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="cart-recovery-strip">
            <div class="cart-recovery-stat">
                <span><i class="fa-solid fa-cart-shopping"></i> Total carts</span>
                <strong>{{ number_format($stats['total']) }}</strong>
            </div>
            <div class="cart-recovery-stat cart-recovery-stat--active">
                <span><i class="fa-solid fa-user-plus"></i> New leads</span>
                <strong>{{ number_format($stats['new']) }}</strong>
            </div>
            <div class="cart-recovery-stat">
                <span><i class="fa-solid fa-sack-dollar"></i> Recoverable value</span>
                <strong>${{ number_format($stats['value'], 0) }}</strong>
            </div>
            <div class="cart-recovery-stat">
                <span><i class="fa-solid fa-check-double"></i> Recovered</span>
                <strong>{{ number_format($stats['recovered']) }}</strong>
            </div>
        </div>

        <div class="page-section-header">
            <div>
                <p class="section-kicker">Recovery workflow</p>
                <h3>Abandoned Cart Queue</h3>
            </div>
            <a href="{{ route('admin.abandoned-carts.export', request()->query()) }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-file-export"></i><span>Export CSV</span>
            </a>
        </div>

        <x-filter-card :action="route('admin.abandoned-carts.index')" class="cart-recovery-filter-card">
            <x-select name="status" size="sm" :value="request('status')" placeholder="Any recovery status" :options="\App\Models\AbandonedCart::STATUSES" />
            <x-select name="age" size="sm" :value="request('age')" placeholder="Any age" :options="\App\Services\AbandonedCartService::AGE_FILTERS" />
            <x-select name="value" size="sm" :value="request('value')" placeholder="Any value" :options="\App\Services\AbandonedCartService::VALUE_FILTERS" />
        </x-filter-card>

        <x-admin.table-card class="cart-recovery-table-card">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right><x-search-input name="search" placeholder="Search customer, email, phone or token..." /></x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Cart</th>
                        <th>Last activity</th>
                        <th>Value</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($carts as $cart)
                        <tr>
                            <td>
                                <strong>{{ $cart->customer_name ?: 'Guest customer' }}</strong>
                                <small class="d-block text-gray-400">{{ $cart->customer_email ?: 'No email captured' }}</small>
                            </td>
                            <td>
                                <span class="count-pill">{{ $cart->items_count }} item(s)</span>
                                <small class="d-block text-gray-400 mt-1">{{ $cart->cart_token ?: 'Manual/future tracking' }}</small>
                            </td>
                            <td>
                                {{ $cart->last_activity_at?->format('M d, Y g:i A') ?: '-' }}
                                <small class="d-block text-gray-400">{{ $cart->last_activity_at?->diffForHumans() }}</small>
                            </td>
                            <td><strong>${{ number_format((float) $cart->subtotal, 2) }}</strong></td>
                            <td><span class="status-chip {{ $cart->statusBadge() }}">{{ $cart->statusLabel() }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.abandoned-carts.show', $cart) }}" class="ghost-button ghost-button--panel">
                                    <i class="fa-solid fa-eye"></i><span>View</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-admin.empty-state icon="fa-solid fa-cart-arrow-down" title="No abandoned carts found"
                                    message="Admin tracking is ready. Storefront capture can be connected later when frontend work starts." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer><x-table-footer :paginator="$carts" label="abandoned carts" /></x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
