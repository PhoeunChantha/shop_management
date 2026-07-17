<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Sales</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Customers') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page customers-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Customer management</p>
                <h3>Customers</h3>
                <p class="customers-lede">Review lifetime spend, order frequency, and recent activity from checkout history.</p>
            </div>
        </div>

        <div class="customer-stat-strip">
            <div class="customer-stat">
                <span>Total customers</span>
                <strong>{{ number_format($stats['customers']) }}</strong>
            </div>
            <div class="customer-stat">
                <span>Repeat buyers</span>
                <strong>{{ number_format($stats['repeat']) }}</strong>
            </div>
            <div class="customer-stat">
                <span>Registered</span>
                <strong>{{ number_format($stats['registered']) }}</strong>
            </div>
            <div class="customer-stat customer-stat--revenue">
                <span>Customer revenue</span>
                <strong>${{ number_format($stats['revenue'], 2) }}</strong>
            </div>
        </div>

        <x-filter-card :action="route('admin.customers.index')" class="customer-filter-card"
            :grid="'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3'">
            <x-slot:hidden>
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <input type="hidden" name="search" value="{{ request('search') }}">
            </x-slot:hidden>

            <x-select name="spend" size="sm" :value="request('spend')" placeholder="All customers"
                :options="['new' => 'One-time buyers', 'repeat' => 'Repeat buyers', 'vip' => 'VIP $500+']" />

            <x-select name="tag_id" size="sm" :value="request('tag_id')" placeholder="All tags"
                :options="$tags->pluck('name', 'id')->all()" />

            <x-select name="sort" size="sm" :value="request('sort', 'last_order')" placeholder="Sort by"
                :options="['last_order' => 'Last order', 'lifetime_spend' => 'Lifetime spend', 'orders_count' => 'Order count', 'customer_name' => 'Customer name']" />

        </x-filter-card>

        <x-admin.table-card class="mt-3 customers-table-card" bulk>
            <x-slot:bulkBar>
                <div class="bulk-bar customer-bulk-bar" x-show="count > 0" x-cloak>
                    <span class="bulk-bar__count">
                        <i class="fa-solid fa-check-double"></i>
                        <span x-text="count"></span> selected
                    </span>
                    <form method="POST" action="{{ route('admin.customers.bulk-status') }}" class="bulk-bar__form">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="1">
                        <template x-for="email in selected" :key="'customer-enable-' + email">
                            <input type="hidden" name="emails[]" :value="email">
                        </template>
                        <button type="submit" class="bulk-btn">
                            <i class="fa-solid fa-toggle-on"></i> Enable
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.customers.bulk-status') }}" class="bulk-bar__form">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="0">
                        <template x-for="email in selected" :key="'customer-disable-' + email">
                            <input type="hidden" name="emails[]" :value="email">
                        </template>
                        <button type="submit" class="bulk-btn">
                            <i class="fa-solid fa-toggle-off"></i> Disable
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.customers.bulk-export') }}" class="bulk-bar__form">
                        @csrf
                        <template x-for="email in selected" :key="'customer-export-' + email">
                            <input type="hidden" name="emails[]" :value="email">
                        </template>
                        <button type="submit" class="bulk-btn">
                            <i class="fa-solid fa-file-export"></i> Export selected
                        </button>
                    </form>
                    <button type="button" class="bulk-btn bulk-btn--danger" @click="confirmingDelete = true">
                        <i class="fa-solid fa-trash"></i> Delete
                    </button>
                    <button type="button" class="bulk-bar__clear" @click="clear()">
                        <i class="fa-solid fa-xmark"></i> Clear
                    </button>
                </div>

                <div class="modal-backdrop-premium" x-show="confirmingDelete" x-cloak style="display:none;"
                    @keydown.escape.window="confirmingDelete = false" @click.self="confirmingDelete = false">
                    <div class="delete-modal customer-delete-modal">
                        <div class="modal-warning-icon">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <h3>Delete selected customers?</h3>
                            <p>
                                This removes <strong><span x-text="count"></span> selected customer(s)</strong> from the admin customer list.
                                Their order history will stay available in Orders.
                            </p>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="modal-cancel" @click="confirmingDelete = false">Cancel</button>
                            <form method="POST" action="{{ route('admin.customers.bulk-destroy') }}" class="mb-0">
                                @csrf
                                @method('DELETE')
                                <template x-for="email in selected" :key="'customer-delete-' + email">
                                    <input type="hidden" name="emails[]" :value="email">
                                </template>
                                <button type="submit" class="modal-delete">
                                    <i class="fa-solid fa-trash"></i>
                                    <span>Delete Customers</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </x-slot:bulkBar>

            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" :options="[10, 25, 50, 100]" />
                    </x-slot:left>
                    <x-slot:right>
                        <div class="customers-toolbar-right">
                            <span class="customers-toolbar-note">Grouped by checkout email</span>
                            <x-search-input name="search" placeholder="Search name, email or phone..." />
                        </div>
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table customer-table">
                <thead>
                    <tr>
                        <th class="bulk-check-col">
                            <input type="checkbox" class="bulk-check" @change="toggleAll($event)"
                                :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="Select all customers">
                        </th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Orders</th>
                        <th>Lifetime spend</th>
                        <th>Avg. order</th>
                        <th>First order</th>
                        <th>Last order</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" data-row-check value="{{ $customer->email_key }}"
                                    x-model="selected" aria-label="Select customer">
                            </td>
                            <td>
                                <div class="customer-cell">
                                    <span class="customer-avatar">{{ strtoupper(mb_substr($customer->customer_name ?: $customer->customer_email, 0, 1)) }}</span>
                                    <div>
                                        <strong>{{ $customer->customer_name ?: 'Guest customer' }}</strong>
                                        <span>{{ $customer->orders_count > 1 ? 'Repeat buyer' : 'First-time buyer' }}</span>
                                        @if ($customer->tags->isNotEmpty())
                                            <div class="customer-mini-tags">
                                                @foreach ($customer->tags as $tag)
                                                    <em style="--tag-color: {{ $tag->color }}">{{ $tag->name }}</em>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="customer-contact">
                                    <span>{{ $customer->customer_email }}</span>
                                    @if ($customer->customer_phone)
                                        <small>{{ $customer->customer_phone }}</small>
                                    @endif
                                </div>
                            </td>
                            <td><span class="count-pill">{{ number_format($customer->orders_count) }}</span></td>
                            <td class="dash-table__amt">${{ number_format((float) $customer->lifetime_spend, 2) }}</td>
                            <td>${{ number_format((float) $customer->average_order_value, 2) }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($customer->first_order_at)->format('M d, Y') }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($customer->last_order_at)->format('M d, Y') }}</td>
                            <td>
                                <span class="status-chip {{ $customer->profile_status ? 'st-active' : 'st-inactive' }}">
                                    {{ $customer->profile_status ? 'Enabled' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.customers.show', $customer->email_key) }}" class="orders-view">
                                    <i class="fa-solid fa-eye"></i><span>View</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <x-admin.empty-state
                                    icon="fa-solid fa-user-group"
                                    title="No customers found"
                                    message="Customers will appear here after orders are placed."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer>
                <x-table-footer :paginator="$customers" label="customers" />
            </x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
