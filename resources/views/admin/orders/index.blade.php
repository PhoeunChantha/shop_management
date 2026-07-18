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

        {{-- KPI stat bar --}}
        <div class="order-stats">
            @php($cards = [
                ['label' => 'Revenue', 'value' => '$' . number_format($stats['revenue'], 2), 'icon' => 'fa-sack-dollar', 'tone' => 'emerald'],
                ['label' => 'Orders', 'value' => number_format($stats['orders']), 'icon' => 'fa-receipt', 'tone' => 'blue'],
                ['label' => 'Pending', 'value' => number_format($stats['pending']), 'icon' => 'fa-clock', 'tone' => 'amber'],
                ['label' => 'Avg. order value', 'value' => '$' . number_format($stats['aov'], 2), 'icon' => 'fa-chart-line', 'tone' => 'violet'],
                ['label' => 'Refunded', 'value' => '$' . number_format($stats['refunded'], 2), 'icon' => 'fa-rotate-left', 'tone' => 'rose'],
            ])
            @foreach ($cards as $c)
                <div class="order-stat">
                    <span class="order-stat__icon order-stat__icon--{{ $c['tone'] }}"><i class="fa-solid {{ $c['icon'] }}"></i></span>
                    <div>
                        <div class="order-stat__value">{{ $c['value'] }}</div>
                        <div class="order-stat__label">{{ $c['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        @include('admin.saved-views._bar', ['scope' => 'orders', 'icon' => 'fa-receipt', 'color' => '#2563eb'])

        {{-- Filters --}}
        <x-filter-card :action="route('admin.orders.index')" :grid="'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3'">
            <x-slot:hidden>
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            </x-slot:hidden>

            <x-select name="status" size="sm" label="Status" :value="request('status')" placeholder="Any status"
                :options="\App\Enums\OrderStatus::options()" />

            <x-select name="payment_status" size="sm" label="Payment" :value="request('payment_status')" placeholder="Any payment"
                :options="\App\Enums\PaymentStatus::options()" />

            <x-select name="customer" size="sm" label="Customer" :value="request('customer')" placeholder="Any customer"
                :options="$customers" searchable />

            <x-select name="price" size="sm" label="Price" :value="request('price')" placeholder="Any price"
                :options="$priceRanges" />

            <div class="form-field">
                <label>Date range</label>
                <div class="daterange-control">
                    <i class="fa-regular fa-calendar"></i>
                    <input type="text" class="form-input" data-daterange placeholder="Any date"
                        readonly autocomplete="off"
                        value="{{ request('date_from') && request('date_to') ? \Illuminate\Support\Carbon::parse(request('date_from'))->format('M d, Y') . ' – ' . \Illuminate\Support\Carbon::parse(request('date_to'))->format('M d, Y') : '' }}">
                </div>
                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ request('date_to') }}">
            </div>
        </x-filter-card>

        <x-admin.table-card class="mt-3 orders-panel">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" />
                    </x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="Search order #, name or email..." />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th style="width:80px;">Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end" style="width:96px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="dash-table__id">{{ $order->order_number }}</a>
                                </td>
                                <td>
                                    <div class="orders-cust">
                                        <span class="orders-avatar">{{ strtoupper(mb_substr($order->customer_name ?: '?', 0, 1)) }}</span>
                                        <div>
                                            <div class="orders-cust__name">{{ $order->customer_name }}</div>
                                            <div class="orders-cust__email">{{ $order->customer_email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-variant-numeric: tabular-nums;">{{ (int) $order->details_sum_quantity }}</td>
                                <td class="dash-table__amt">${{ number_format($order->grand_total, 2) }}</td>
                                <td>
                                    <span class="status-chip {{ $order->payment_status->badge() }}">{{ $order->payment_status->label() }}</span>
                                    <div class="orders-pay__method">
                                        <i class="fa-regular fa-credit-card"></i>{{ $order->payment_method ? strtoupper($order->payment_method) : '—' }}
                                    </div>
                                </td>
                                <td><span class="status-chip {{ $order->status->badge() }}">{{ $order->status->label() }}</span></td>
                                <td class="dash-table__date">{{ ($order->placed_at ?? $order->created_at)?->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <x-table-actions>
                                            <a href="{{ route('admin.orders.show', $order->id) }}"
                                                class="table-actions__item table-actions__item--edit" role="menuitem">
                                                <i class="fa-solid fa-eye"></i><span>View</span>
                                            </a>
                                            <a href="{{ route('admin.orders.invoice', $order->id) }}" target="_blank"
                                                class="table-actions__item" role="menuitem">
                                                <i class="fa-solid fa-file-invoice"></i><span>Invoice</span>
                                            </a>
                                            <a href="{{ route('admin.orders.packing-slip', $order->id) }}" target="_blank"
                                                class="table-actions__item" role="menuitem">
                                                <i class="fa-solid fa-box-open"></i><span>Packing slip</span>
                                            </a>
                                        </x-table-actions>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <x-admin.empty-state
                                        icon="fa-solid fa-receipt"
                                        title="No orders found"
                                        message="Orders will appear here once customers check out."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
            </table>

            <x-slot:footer>
                <x-table-footer :paginator="$orders" label="orders" />
            </x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
