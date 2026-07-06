<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Sales</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Orders') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Order table</p>
                <h3>All Orders</h3>
            </div>
        </div>

        {{-- Filters --}}
        <x-filter-card :action="route('admin.orders.index')" :grid="'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3'">
            <x-slot:hidden>
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            </x-slot:hidden>

            <x-select name="status" size="sm" :value="request('status')" placeholder="Any status"
                :options="\App\Enums\OrderStatus::options()" />

            <div class="form-field">
                <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}" placeholder="From">
            </div>
            <div class="form-field">
                <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}" placeholder="To">
            </div>
        </x-filter-card>

        <section class="premium-card mt-3">
            <x-table-loader />

            <x-table-toolbar>
                <x-slot:left>
                    <div class="result-badge">
                        <i class="fa-solid fa-receipt"></i>
                        <span>{{ $orders->total() }} result{{ $orders->total() === 1 ? '' : 's' }}</span>
                    </div>
                    <x-per-page-selector :current="$perPage" />
                </x-slot:left>
                <x-slot:right>
                    <x-search-input name="search" placeholder="Search order #, name or email..." />
                </x-slot:right>
            </x-table-toolbar>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th style="width:90px;">Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end" style="width:110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td><strong class="text-gray-900 dark:text-slate-100 font-mono">{{ $order->order_number }}</strong></td>
                                <td>
                                    <div class="text-sm text-gray-800 dark:text-slate-200">{{ $order->customer_name }}</div>
                                    <div class="text-xs text-gray-400 dark:text-slate-500">{{ $order->customer_email }}</div>
                                </td>
                                <td><span class="text-sm text-gray-600 dark:text-slate-300">{{ (int) $order->details_sum_quantity }}</span></td>
                                <td><strong class="text-gray-900 dark:text-slate-100">${{ number_format($order->grand_total, 2) }}</strong></td>
                                <td>
                                    <span class="text-xs {{ $order->isPaid() ? 'text-green-600' : 'text-gray-400' }}">
                                        <i class="fa-solid {{ $order->isPaid() ? 'fa-circle-check' : 'fa-clock' }}"></i>
                                        {{ $order->isPaid() ? 'Paid' : 'Unpaid' }}
                                    </span>
                                    <div class="text-xs text-gray-400 dark:text-slate-500">{{ $order->payment_method ? strtoupper($order->payment_method) : '—' }}</div>
                                </td>
                                <td><span class="status-chip {{ $order->status->badge() }}">{{ $order->status->label() }}</span></td>
                                <td><span class="text-xs text-gray-500 dark:text-slate-400">{{ ($order->placed_at ?? $order->created_at)?->format('M d, Y') }}</span></td>
                                <td>
                                    <div class="action-group">
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="table-action table-action--edit">
                                            <i class="fa-solid fa-eye"></i><span>View</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-receipt"></i>
                                        <strong>No orders found</strong>
                                        <span>Orders will appear here once customers check out.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$orders" label="orders" />
        </section>
    </div>
</x-app-layout>
