<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Sales</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Create Return') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Return setup</p>
                <h3>New Return Request</h3>
            </div>
            <a href="{{ route('admin.returns.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>Back</span>
            </a>
        </div>

        <x-message />

        @if (! $order)
            <section class="premium-card form-panel">
                <div class="form-panel-header">
                    <div class="form-panel-icon"><i class="fa-solid fa-receipt"></i></div>
                    <div><h3>Select order</h3><p>Choose the order that needs a return or refund workflow.</p></div>
                </div>
                <div class="form-panel-body">
                    <div class="return-order-grid">
                        @foreach ($orders as $candidate)
                            <a href="{{ route('admin.returns.create', ['order_id' => $candidate->id]) }}" class="return-order-card">
                                <strong>{{ $candidate->order_number }}</strong>
                                <span>{{ $candidate->customer_name }} · ${{ number_format((float) $candidate->grand_total, 2) }}</span>
                                <em>{{ $candidate->details->count() }} line(s)</em>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @else
            <form action="{{ route('admin.returns.store') }}" method="POST" class="premium-card form-panel">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->id }}">

                <div class="form-panel-header">
                    <div class="form-panel-icon"><i class="fa-solid fa-rotate-left"></i></div>
                    <div>
                        <h3>{{ $order->order_number }}</h3>
                        <p>{{ $order->customer_name }} · {{ $order->customer_email }}</p>
                    </div>
                </div>

                <div class="return-form-body">
                    <section class="return-form-section">
                        <p class="section-kicker mb-3">Reason and notes</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-field">
                                <label for="reason">Reason</label>
                                <select name="reason" id="reason" class="form-input" required>
                                    @foreach (\App\Models\ReturnRequest::REASONS as $value => $label)
                                        <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('reason')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-field">
                                <label for="refund_amount">Planned refund amount</label>
                                <input type="number" step="0.01" min="0" name="refund_amount" id="refund_amount"
                                    value="{{ old('refund_amount', $order->grand_total) }}" class="form-input">
                                @error('refund_amount')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-field">
                                <label for="customer_note">Customer note</label>
                                <textarea name="customer_note" id="customer_note" rows="3" class="form-input">{{ old('customer_note') }}</textarea>
                            </div>
                            <div class="form-field">
                                <label for="admin_note">Internal note</label>
                                <textarea name="admin_note" id="admin_note" rows="3" class="form-input">{{ old('admin_note') }}</textarea>
                            </div>
                        </div>
                    </section>

                    <section class="return-form-section">
                        <p class="section-kicker mb-3">Return items</p>
                        <div class="premium-table-wrap">
                            <table class="premium-table">
                                <thead><tr><th>Return</th><th>Product</th><th>Ordered</th><th>Qty</th><th>Condition</th><th class="text-end">Line total</th></tr></thead>
                                <tbody>
                                    @foreach ($order->details as $item)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="items[{{ $loop->index }}][return]" value="1" class="bulk-check" @checked(old("items.{$loop->index}.return", true))>
                                                <input type="hidden" name="items[{{ $loop->index }}][order_detail_id]" value="{{ $item->id }}">
                                            </td>
                                            <td><strong>{{ $item->name }}</strong><small class="d-block text-gray-400">{{ $item->sku ?: 'No SKU' }}</small></td>
                                            <td>{{ $item->quantity }}</td>
                                            <td><input type="number" name="items[{{ $loop->index }}][quantity]" value="{{ old("items.{$loop->index}.quantity", $item->quantity) }}" min="0" max="{{ $item->quantity }}" class="form-input return-qty-input"></td>
                                            <td><input type="text" name="items[{{ $loop->index }}][condition]" value="{{ old("items.{$loop->index}.condition") }}" class="form-input" placeholder="Unopened, damaged..."></td>
                                            <td class="text-end">${{ number_format((float) $item->line_total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @error('items')<p class="text-red-500 text-sm mt-2">{{ $message }}</p>@enderror
                    </section>
                </div>

                <div class="form-panel-footer">
                    <a href="{{ route('admin.returns.index') }}" class="form-cancel-button">Cancel</a>
                    <button type="submit" class="form-submit-button"><i class="fa-solid fa-check"></i>Create Return</button>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
