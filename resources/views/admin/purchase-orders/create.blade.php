<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Restock</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('New Purchase Order') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <form method="POST" action="{{ route('admin.purchase-orders.store') }}" class="restock-po-form">
            @csrf

            <section class="premium-card restock-form-card">
                <div class="form-section__header">
                    <span class="form-section__icon"><i class="fa-solid fa-clipboard-list"></i></span>
                    <div>
                        <p class="section-kicker">Purchase order</p>
                        <h3>Order details</h3>
                    </div>
                </div>
                <div class="form-grid">
                    <x-select name="supplier_id" label="Supplier" :options="$suppliers" optionValue="id" optionLabel="name" :value="old('supplier_id')" placeholder="Select supplier" searchable required />
                    <x-select name="status" label="Initial status" :options="['draft' => 'Draft', 'ordered' => 'Ordered']" :value="old('status', 'draft')" required />
                    <div class="form-field">
                        <label for="expected_at">Expected arrival</label>
                        <input type="date" name="expected_at" id="expected_at" class="form-input" value="{{ old('expected_at') }}">
                    </div>
                    <div class="form-field form-field--wide">
                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" class="form-input" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </section>

            <section class="premium-card restock-form-card mt-3">
                <div class="form-section__header">
                    <span class="form-section__icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                    <div>
                        <p class="section-kicker">Incoming items</p>
                        <h3>Products to restock</h3>
                    </div>
                </div>
                <div class="restock-items">
                    @for ($i = 0; $i < 6; $i++)
                        <div class="restock-item-row">
                            <x-select name="items[{{ $i }}][stockable]" size="sm" :options="$stockables" :value="old("items.$i.stockable")" placeholder="Select product or variant" searchable />
                            <input type="number" name="items[{{ $i }}][quantity_ordered]" class="form-input" min="1" placeholder="Qty" value="{{ old("items.$i.quantity_ordered") }}">
                            <input type="number" step="0.01" name="items[{{ $i }}][unit_cost]" class="form-input" min="0" placeholder="Unit cost" value="{{ old("items.$i.unit_cost") }}">
                        </div>
                    @endfor
                </div>
                @error('items')<p class="text-red-500 text-sm mt-2">{{ $message }}</p>@enderror
            </section>

            <div class="form-actions-sticky">
                <a href="{{ route('admin.purchase-orders.index') }}" class="ghost-button ghost-button--panel">
                    <i class="fa-solid fa-arrow-left"></i><span>Cancel</span>
                </a>
                <button type="submit" class="premium-button premium-button--dark">
                    <i class="fa-solid fa-floppy-disk"></i><span>Create purchase order</span>
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
