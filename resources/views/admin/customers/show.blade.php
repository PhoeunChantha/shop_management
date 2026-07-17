<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Sales</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Customer Profile') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page customer-profile-page">
        <div class="customer-profile-hero">
            <div class="customer-profile-identity">
                <span class="customer-profile-avatar">{{ strtoupper(mb_substr($profile->customer_name ?: $profile->customer_email, 0, 1)) }}</span>
                <div>
                    <p class="section-kicker">Customer profile</p>
                    <h3>{{ $profile->customer_name ?: 'Guest customer' }}</h3>
                    <div class="customer-profile-meta">
                        <span><i class="fa-regular fa-envelope"></i>{{ $profile->customer_email }}</span>
                        @if ($profile->customer_phone)
                            <span><i class="fa-solid fa-phone"></i>{{ $profile->customer_phone }}</span>
                        @endif
                        @if ($profile->shipping_city || $profile->shipping_country)
                            <span><i class="fa-solid fa-location-dot"></i>{{ collect([$profile->shipping_city, $profile->shipping_country])->filter()->join(', ') }}</span>
                        @endif
                    </div>
                    @if ($crmProfile->tags->isNotEmpty())
                        <div class="customer-mini-tags customer-mini-tags--hero">
                            @foreach ($crmProfile->tags as $tag)
                                <em style="--tag-color: {{ $tag->color }}">{{ $tag->name }}</em>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <a href="{{ route('admin.customers.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>Back</span>
            </a>
        </div>

        <div class="customer-stat-strip">
            <div class="customer-stat">
                <span>Total orders</span>
                <strong>{{ number_format($profile->orders_count) }}</strong>
            </div>
            <div class="customer-stat customer-stat--revenue">
                <span>Lifetime spend</span>
                <strong>${{ number_format((float) $profile->lifetime_spend, 2) }}</strong>
            </div>
            <div class="customer-stat">
                <span>Average order</span>
                <strong>${{ number_format((float) $profile->average_order_value, 2) }}</strong>
            </div>
            <div class="customer-stat">
                <span>Last order</span>
                <strong>{{ \Illuminate\Support\Carbon::parse($profile->last_order_at)->format('M d') }}</strong>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mt-4">
            <div class="xl:col-span-2">
                <x-admin.table-card class="customer-orders-card">
                    <x-slot:toolbar>
                        <x-table-toolbar>
                            <x-slot:left>
                                <div class="table-titlebar p-0">
                                    <div>
                                        <h3>Order History</h3>
                                        <p>{{ $orders->total() }} order{{ $orders->total() === 1 ? '' : 's' }} from this customer</p>
                                    </div>
                                </div>
                            </x-slot:left>
                        </x-table-toolbar>
                    </x-slot:toolbar>

                    <table class="premium-table customer-order-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td><a href="{{ route('admin.orders.show', $order->id) }}" class="dash-table__id">{{ $order->order_number }}</a></td>
                                    <td>{{ (int) $order->details_sum_quantity }}</td>
                                    <td class="dash-table__amt">${{ number_format((float) $order->grand_total, 2) }}</td>
                                    <td><span class="status-chip {{ $order->payment_status->badge() }}">{{ $order->payment_status->label() }}</span></td>
                                    <td><span class="status-chip {{ $order->status->badge() }}">{{ $order->status->label() }}</span></td>
                                    <td>{{ ($order->placed_at ?? $order->created_at)?->format('M d, Y') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="orders-view">
                                            <i class="fa-solid fa-eye"></i><span>View</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <x-slot:footer>
                        <x-table-footer :paginator="$orders" label="orders" />
                    </x-slot:footer>
                </x-admin.table-card>
            </div>

            <aside class="d-flex flex-column gap-4">
                <section class="premium-card customer-side-card">
                    <p class="section-kicker mb-3">CRM Notes</p>
                    <form method="POST" action="{{ route('admin.customers.crm.update', $profile->customer_email) }}" class="customer-crm-form">
                        @csrf
                        @method('PATCH')

                        <label class="customer-crm-label" for="customer-notes">Internal notes</label>
                        <textarea id="customer-notes" name="notes" class="form-input customer-crm-notes"
                            placeholder="Add preferences, support context, delivery notes, or account risk details.">{{ old('notes', $crmProfile->notes) }}</textarea>

                        <label class="customer-crm-label">Tags</label>
                        <div class="customer-crm-tags">
                            @foreach ($tags as $tag)
                                <label style="--tag-color: {{ $tag->color }}">
                                    <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                        @checked(in_array($tag->id, old('tags', $crmProfile->tags->pluck('id')->all())))>
                                    <span>{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <button type="submit" class="premium-button premium-button--dark w-100 justify-center mt-3">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save CRM details</span>
                        </button>
                    </form>
                </section>

                <section class="premium-card customer-side-card">
                    <p class="section-kicker mb-3">Customer Type</p>
                    <div class="customer-tag-stack">
                        <span class="{{ $profile->orders_count > 1 ? 'is-repeat' : '' }}">
                            <i class="fa-solid {{ $profile->orders_count > 1 ? 'fa-rotate' : 'fa-user-plus' }}"></i>
                            {{ $profile->orders_count > 1 ? 'Repeat buyer' : 'First-time buyer' }}
                        </span>
                        @if ((float) $profile->lifetime_spend >= 500)
                            <span class="is-vip"><i class="fa-solid fa-crown"></i> VIP customer</span>
                        @endif
                    </div>
                    <div class="customer-date-list">
                        <div><span>First order</span><strong>{{ \Illuminate\Support\Carbon::parse($profile->first_order_at)->format('M d, Y') }}</strong></div>
                        <div><span>Last order</span><strong>{{ \Illuminate\Support\Carbon::parse($profile->last_order_at)->format('M d, Y') }}</strong></div>
                    </div>
                </section>

                <section class="premium-card customer-side-card">
                    <p class="section-kicker mb-3">Top Products</p>
                    @forelse ($topProducts as $product)
                        <div class="customer-top-product">
                            <div>
                                <strong>{{ $product->name }}</strong>
                                <span>{{ $product->sku ?: 'No SKU' }}</span>
                            </div>
                            <em>{{ number_format($product->quantity) }} sold</em>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 mb-0">No product history yet.</p>
                    @endforelse
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
