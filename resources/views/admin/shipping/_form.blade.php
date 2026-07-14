@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $m = $method ?? null;
    $typeValue = old('type', $m?->type->value ?? 'flat');
@endphp

<form action="{{ $action }}" method="POST" x-data="{ type: '{{ $typeValue }}' }">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="form-field col-span-2 md:col-span-1">
            <label for="name">Name <span class="text-red-500">*</span></label>
            <input value="{{ old('name', $m->name ?? '') }}" type="text" name="name" id="name"
                class="form-input" placeholder="e.g. Standard shipping" required>
            @error('name')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="type">Rate type <span class="text-red-500">*</span></label>
            <select name="type" id="type" class="form-input" x-model="type">
                @foreach (\App\Enums\ShippingRateType::options() as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1" x-show="type !== 'free'" x-cloak>
            <label for="rate" x-text="type === 'free_over' ? 'Rate (under threshold)' : 'Rate'"></label>
            <input value="{{ old('rate', $m->rate ?? '0.00') }}" type="number" step="0.01" min="0" name="rate"
                id="rate" class="form-input" placeholder="0.00">
            @error('rate')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1" x-show="type === 'free_over'" x-cloak>
            <label for="free_over_amount">Free over (subtotal)</label>
            <input value="{{ old('free_over_amount', $m->free_over_amount ?? '') }}" type="number" step="0.01" min="0"
                name="free_over_amount" id="free_over_amount" class="form-input" placeholder="e.g. 50.00">
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">Orders at/above this subtotal ship free.</small>
            @error('free_over_amount')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="delivery_time">Delivery estimate</label>
            <input value="{{ old('delivery_time', $m->delivery_time ?? '') }}" type="text" name="delivery_time"
                id="delivery_time" class="form-input" placeholder="e.g. 2–4 business days">
            @error('delivery_time')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2">
            <label for="description">Description</label>
            <input value="{{ old('description', $m->description ?? '') }}" type="text" name="description"
                id="description" class="form-input" placeholder="Shown to customers at checkout">
            @error('description')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="sort_order">Sort order</label>
            <input value="{{ old('sort_order', $m->sort_order ?? 0) }}" type="number" min="0" name="sort_order"
                id="sort_order" class="form-input" placeholder="0">
            @error('sort_order')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $m->status ?? 1) == 1 ? 'selected' : '' }}>Enabled</option>
                <option value="0" {{ old('status', $m->status ?? 1) == 0 ? 'selected' : '' }}>Disabled</option>
            </select>
            @error('status')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.shipping.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
