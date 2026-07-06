<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Sales</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Order') }} {{ $order->order_number }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Order detail</p>
                <h3 class="d-flex align-items-center gap-2">
                    {{ $order->order_number }}
                    <span class="status-chip {{ $order->status->badge() }}">{{ $order->status->label() }}</span>
                </h3>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">
                    Placed {{ ($order->placed_at ?? $order->created_at)?->format('M d, Y \a\t g:i A') }}
                </p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>Back</span>
            </a>
        </div>

        <x-message />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Left: items + money --}}
            <div class="lg:col-span-2 d-flex flex-column gap-4">
                <section class="premium-card">
                    <div class="table-titlebar">
                        <div><h3>Items</h3><p>{{ $order->details->count() }} line{{ $order->details->count() === 1 ? '' : 's' }} · {{ $order->details->sum('quantity') }} units</p></div>
                    </div>
                    <div class="premium-table-wrap">
                        <table class="premium-table">
                            <thead>
                                <tr><th>Product</th><th>SKU</th><th>Price</th><th>Qty</th><th class="text-end">Total</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($order->details as $item)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if ($item->image)
                                                    <img src="{{ Imageurl($item->image, 'products') }}" alt="" class="w-10 h-10 object-cover rounded border dark:border-white/10">
                                                @else
                                                    <span class="d-inline-flex align-items-center justify-content-center rounded bg-gray-100 text-gray-300 dark:bg-white/10" style="width:40px;height:40px;"><i class="fa-regular fa-image"></i></span>
                                                @endif
                                                <div>
                                                    <strong class="text-gray-900 dark:text-slate-100">{{ $item->name }}</strong>
                                                    @if ($item->variant_label)<div class="text-xs text-gray-400 dark:text-slate-500">{{ $item->variant_label }}</div>@endif
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="font-mono text-xs text-gray-500">{{ $item->sku ?: '—' }}</span></td>
                                        <td>${{ number_format($item->price, 2) }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td class="text-end"><strong>${{ number_format($item->line_total, 2) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="order-money">
                        <div><span>Subtotal</span><span>${{ number_format($order->subtotal, 2) }}</span></div>
                        @if ($order->discount_total > 0)
                            <div><span>Discount {{ $order->coupon_code ? "($order->coupon_code)" : '' }}</span><span class="text-green-600">−${{ number_format($order->discount_total, 2) }}</span></div>
                        @endif
                        <div><span>Shipping</span><span>{{ $order->shipping_total > 0 ? '$'.number_format($order->shipping_total, 2) : 'Free' }}</span></div>
                        <div><span>Tax</span><span>${{ number_format($order->tax_total, 2) }}</span></div>
                        <div class="order-money__total"><span>Total</span><span>${{ number_format($order->grand_total, 2) }}</span></div>
                    </div>
                </section>

                @if ($order->customer_note)
                    <section class="premium-card p-4">
                        <p class="section-kicker mb-1">Customer note</p>
                        <p class="text-sm text-gray-700 dark:text-slate-300 mb-0">{{ $order->customer_note }}</p>
                    </section>
                @endif
            </div>

            {{-- Right: fulfilment + customer + shipping + payment --}}
            <aside class="d-flex flex-column gap-4">
                <section class="premium-card form-panel">
                    <div class="form-panel-header">
                        <div class="form-panel-icon"><i class="fa-solid fa-truck-fast"></i></div>
                        <div><h3>Fulfilment</h3><p>Update status, tracking and notes.</p></div>
                    </div>
                    <form action="{{ route('admin.orders.update', $order->id) }}" method="POST" class="form-panel-body d-flex flex-column gap-3">
                        @csrf
                        @method('PATCH')
                        <div class="form-field">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-input">
                                @foreach (\App\Enums\OrderStatus::options() as $val => $label)
                                    <option value="{{ $val }}" @selected(old('status', $order->status->value) === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-field">
                            <label for="tracking_number">Tracking number</label>
                            <input type="text" name="tracking_number" id="tracking_number" class="form-input"
                                value="{{ old('tracking_number', $order->tracking_number) }}" placeholder="e.g. 1Z999AA10123456784">
                            @error('tracking_number')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-field">
                            <label for="admin_note">Internal note</label>
                            <textarea name="admin_note" id="admin_note" class="form-input" rows="3" placeholder="Not shown to the customer">{{ old('admin_note', $order->admin_note) }}</textarea>
                            @error('admin_note')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="form-submit-button"><i class="fa-solid fa-check"></i> Save changes</button>
                    </form>
                </section>

                <section class="premium-card p-4">
                    <p class="section-kicker mb-2">Customer</p>
                    <div class="text-sm">
                        <div class="font-semibold text-gray-900 dark:text-slate-100">{{ $order->customer_name }}</div>
                        <div class="text-gray-500 dark:text-slate-400">{{ $order->customer_email }}</div>
                        @if ($order->customer_phone)<div class="text-gray-500 dark:text-slate-400">{{ $order->customer_phone }}</div>@endif
                        <div class="mt-1 text-xs {{ $order->user ? 'text-blue-500' : 'text-gray-400' }}">
                            <i class="fa-solid {{ $order->user ? 'fa-user-check' : 'fa-user-xmark' }}"></i>
                            {{ $order->user ? 'Registered account' : 'Guest checkout' }}
                        </div>
                    </div>
                </section>

                <section class="premium-card p-4">
                    <p class="section-kicker mb-2">Shipping</p>
                    <div class="text-sm text-gray-700 dark:text-slate-300">
                        {{ $order->shipping_address }}<br>
                        {{ collect([$order->shipping_city, $order->shipping_zip])->filter()->join(', ') }}<br>
                        {{ $order->shipping_country }}
                    </div>
                    <div class="text-xs text-gray-400 dark:text-slate-500 mt-2">
                        Method: {{ $order->shipping_method ? ucfirst($order->shipping_method) : '—' }}
                        @if ($order->tracking_number)<br>Tracking: <span class="font-mono">{{ $order->tracking_number }}</span>@endif
                    </div>
                </section>

                <section class="premium-card p-4">
                    <p class="section-kicker mb-2">Payment</p>
                    <div class="text-sm text-gray-700 dark:text-slate-300">
                        Method: {{ $order->payment_method ? strtoupper($order->payment_method) : '—' }}<br>
                        Status:
                        <span class="{{ $order->isPaid() ? 'text-green-600' : 'text-amber-600' }}">
                            {{ $order->isPaid() ? 'Paid'.($order->paid_at ? ' · '.$order->paid_at->format('M d, Y') : '') : 'Unpaid' }}
                        </span>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
