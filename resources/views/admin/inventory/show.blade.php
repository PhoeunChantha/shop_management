<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Catalog</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Inventory') }} · {{ $product->name }}
            </h2>
        </div>
    </x-slot>

    @php
        $isSingle = $product->product_type->value === 'single';
        $rows = $isSingle
            ? collect([(object) ['id' => null, 'label' => 'Base product', 'sku' => $product->sku, 'stock' => (int) $product->stock, 'alert' => (int) $product->low_stock_alert]])
            : $product->variants->map(fn ($v) => (object) ['id' => $v->id, 'label' => $v->variant_label ?: 'Variant', 'sku' => $v->sku, 'stock' => (int) $v->stock, 'alert' => (int) $v->low_stock_alert]);
    @endphp

    <div class="admin-page" x-data="{ open: false, vid: '', label: '', current: 0 }">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Stock control</p>
                <h3 class="d-flex align-items-center gap-2">{{ $product->name }}
                    <span class="status-chip {{ $isSingle ? 'st-draft' : 'st-active' }}">{{ $isSingle ? 'Single' : 'Variable' }}</span>
                </h3>
            </div>
            <a href="{{ route('admin.inventory.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>Back to inventory</span>
            </a>
        </div>

        <x-message />

        {{-- Stock levels --}}
        <x-admin.table-card :loader="false" class="mt-3 orders-panel">
            <x-slot:toolbar>
                <div class="table-titlebar">
                    <div><h3>Stock levels</h3><p>{{ $rows->count() }} stock {{ Str::plural('item', $rows->count()) }}</p></div>
                </div>
            </x-slot:toolbar>

            <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>SKU</th>
                            <th style="width:110px;">On hand</th>
                            <th style="width:110px;">Low alert</th>
                            <th style="width:140px;">Status</th>
                            <th class="text-end" style="width:120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                if ($row->stock <= 0) { $stClass = 'st-inactive'; $stLabel = 'Out of stock'; }
                                elseif ($row->alert > 0 && $row->stock <= $row->alert) { $stClass = 'st-new'; $stLabel = 'Low stock'; }
                                else { $stClass = 'st-active'; $stLabel = 'In stock'; }
                            @endphp
                            <tr>
                                <td class="orders-cust__name">{{ $row->label }}</td>
                                <td><span class="dash-table__id">{{ $row->sku ?: '—' }}</span></td>
                                <td class="dash-table__amt">{{ number_format($row->stock) }}</td>
                                <td style="font-variant-numeric:tabular-nums;">{{ $row->alert ?: '—' }}</td>
                                <td><span class="status-chip {{ $stClass }}">{{ $stLabel }}</span></td>
                                <td class="text-end">
                                    <button type="button" class="orders-view"
                                        @click="open = true; vid = @js((string) $row->id); label = @js($row->label); current = {{ $row->stock }}">
                                        <i class="fa-solid fa-plus-minus"></i><span>Adjust</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
            </table>
        </x-admin.table-card>

        {{-- Movement history --}}
        <x-admin.table-card :loader="false" class="mt-3 orders-panel">
            <x-slot:toolbar>
                <div class="table-titlebar">
                    <div><h3>Movement history</h3><p>Every stock change, most recent first</p></div>
                </div>
            </x-slot:toolbar>

            <table class="dash-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Item</th>
                            <th>Reason</th>
                            <th style="width:90px;">Change</th>
                            <th style="width:100px;">On hand</th>
                            <th>Note</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($product->stockMovements as $m)
                            <tr>
                                <td class="dash-table__date">{{ $m->created_at?->format('M d, Y g:i A') }}</td>
                                <td class="orders-cust__name">{{ $m->variant?->variant_label ?: 'Base product' }}</td>
                                <td>
                                    <span class="status-chip {{ $m->type->badge() }}">
                                        <i class="fa-solid {{ $m->type->icon() }} me-1"></i>{{ $m->type->label() }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold" style="color: {{ $m->quantity >= 0 ? '#059669' : '#e11d48' }};">
                                        {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}
                                    </span>
                                </td>
                                <td class="dash-table__amt">{{ $m->stock_after }}</td>
                                <td><span class="text-sm text-gray-500 dark:text-slate-400">{{ $m->note ?: '—' }}</span></td>
                                <td><span class="text-sm text-gray-500 dark:text-slate-400">{{ $m->actor?->name ?? 'System' }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <x-admin.empty-state
                                        icon="fa-solid fa-clock-rotate-left"
                                        title="No movements yet"
                                        message="Adjust a stock item to start the history."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
            </table>
        </x-admin.table-card>

        {{-- Adjust modal --}}
        <div class="modal-backdrop-premium" x-show="open" x-cloak style="display:none;"
            @keydown.escape.window="open = false" @click.self="open = false">
            <div class="form-modal">
                <div class="form-modal__head">
                    <div class="form-modal__icon"><i class="fa-solid fa-plus-minus"></i></div>
                    <div class="flex-grow-1">
                        <h3>Adjust stock</h3>
                        <p><span x-text="label"></span> · on hand <span x-text="current" class="fw-bold"></span></p>
                    </div>
                    <button type="button" class="form-modal__close" @click="open = false" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <form action="{{ route('admin.inventory.adjust', $product->id) }}" method="POST" class="form-modal__body d-flex flex-column gap-3">
                    @csrf
                    <input type="hidden" name="variant_id" :value="vid">

                    <div class="form-field">
                        <label for="adj_qty">Quantity change <span class="text-red-500">*</span></label>
                        <input type="number" name="quantity" id="adj_qty" class="form-input" required
                            placeholder="e.g. 25 to add, -3 to remove">
                        <small class="text-gray-400 dark:text-slate-500 d-block mt-1">Positive adds stock, negative removes it.</small>
                        @error('quantity')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label for="adj_type">Reason <span class="text-red-500">*</span></label>
                        <select name="type" id="adj_type" class="form-input" required>
                            @foreach (\App\Enums\StockMovementType::manualOptions() as $val => $lbl)
                                <option value="{{ $val }}" @selected($val === 'restock')>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        @error('type')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label for="adj_note">Note</label>
                        <input type="text" name="note" id="adj_note" class="form-input" maxlength="255"
                            placeholder="Optional — e.g. supplier delivery #123">
                        @error('note')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-modal__foot">
                        <button type="button" class="modal-cancel" @click="open = false">Cancel</button>
                        <button type="submit" class="form-submit-button"><i class="fa-solid fa-check"></i> Apply adjustment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
