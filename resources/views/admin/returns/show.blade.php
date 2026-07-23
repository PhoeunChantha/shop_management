<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Sales</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ $return->return_number }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="return-show-hero">
            <div>
                <p class="section-kicker">Return request</p>
                <h3>{{ $return->return_number }}</h3>
                <div class="return-show-tags">
                    <span class="status-chip {{ $return->statusBadge() }}">{{ $return->statusLabel() }}</span>
                    <span class="status-chip {{ $return->refundBadge() }}">{{ $return->refundStatusLabel() }}</span>
                    <a href="{{ route('admin.orders.show', $return->order) }}">{{ $return->order->order_number }}</a>
                </div>
            </div>
            <a href="{{ route('admin.returns.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>Back</span>
            </a>
        </div>

        <x-message />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
            <div class="lg:col-span-2 d-flex flex-column gap-4">
                <section class="premium-card return-detail-card">
                    <p class="section-kicker mb-3">Returned items</p>
                    <table class="premium-table">
                        <thead><tr><th>Product</th><th>Qty</th><th>Condition</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            @foreach ($return->items as $item)
                                <tr>
                                    <td><strong>{{ $item->name }}</strong><small class="d-block text-gray-400">{{ $item->sku ?: 'No SKU' }}</small></td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ $item->condition ?: 'Not specified' }}</td>
                                    <td class="text-end">${{ number_format((float) $item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>

                <section class="premium-card return-detail-card">
                    <p class="section-kicker mb-3">Notes</p>
                    <div class="return-note-grid">
                        <div><span>Reason</span><strong>{{ $return->reasonLabel() }}</strong></div>
                        <div><span>Customer note</span><p>{{ $return->customer_note ?: 'No customer note.' }}</p></div>
                        <div><span>Internal note</span><p>{{ $return->admin_note ?: 'No internal note.' }}</p></div>
                    </div>
                </section>
            </div>

            <aside class="d-flex flex-column gap-4">
                <section class="premium-card form-panel">
                    <div class="form-panel-header">
                        <div class="form-panel-icon"><i class="fa-solid fa-money-bill-transfer"></i></div>
                        <div><h3>Workflow</h3><p>Update return and refund state.</p></div>
                    </div>
                    <form action="{{ route('admin.returns.update', $return) }}" method="POST" class="form-panel-body d-flex flex-column gap-3">
                        @csrf
                        @method('PATCH')
                        <div class="form-field">
                            <label for="status">Return status</label>
                            <select name="status" id="status" class="form-input">
                                @foreach (\App\Models\ReturnRequest::STATUSES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $return->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="refund_status">Refund status</label>
                            <select name="refund_status" id="refund_status" class="form-input">
                                @foreach (\App\Models\ReturnRequest::REFUND_STATUSES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('refund_status', $return->refund_status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="refund_amount">Refund amount</label>
                            <input type="number" step="0.01" min="0" name="refund_amount" id="refund_amount" class="form-input" value="{{ old('refund_amount', $return->refund_amount) }}">
                        </div>
                        <div class="form-field">
                            <label for="admin_note">Internal note</label>
                            <textarea name="admin_note" id="admin_note" rows="4" class="form-input">{{ old('admin_note', $return->admin_note) }}</textarea>
                        </div>
                        <button type="submit" class="form-submit-button"><i class="fa-solid fa-check"></i>Save workflow</button>
                    </form>
                </section>

                <section class="premium-card return-detail-card">
                    <p class="section-kicker mb-3">Money</p>
                    <dl class="return-money-list">
                        <div><dt>Requested</dt><dd>${{ number_format((float) $return->requested_amount, 2) }}</dd></div>
                        <div><dt>Refund</dt><dd>${{ number_format((float) $return->refund_amount, 2) }}</dd></div>
                        <div><dt>Order total</dt><dd>${{ number_format((float) $return->order->grand_total, 2) }}</dd></div>
                    </dl>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
