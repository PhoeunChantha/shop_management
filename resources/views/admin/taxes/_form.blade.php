@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $r = $rule ?? null;
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="form-field col-span-2 md:col-span-1">
            <label for="name">Name <span class="text-red-500">*</span></label>
            <input value="{{ old('name', $r->name ?? '') }}" type="text" name="name" id="name"
                class="form-input" placeholder="e.g. Standard VAT" required>
            @error('name')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="rate">Rate (%) <span class="text-red-500">*</span></label>
            <input value="{{ old('rate', $r->rate ?? '0.00') }}" type="number" step="0.01" min="0" max="100"
                name="rate" id="rate" class="form-input" placeholder="e.g. 8.50" required>
            @error('rate')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="is_inclusive">Pricing</label>
            <select name="is_inclusive" id="is_inclusive" class="form-input">
                <option value="0" {{ old('is_inclusive', $r->is_inclusive ?? 0) == 0 ? 'selected' : '' }}>Exclusive (added at checkout)</option>
                <option value="1" {{ old('is_inclusive', $r->is_inclusive ?? 0) == 1 ? 'selected' : '' }}>Inclusive (price already includes tax)</option>
            </select>
            @error('is_inclusive')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="country">Country</label>
            <input value="{{ old('country', $r->country ?? '') }}" type="text" name="country" id="country"
                class="form-input" placeholder="Leave blank to apply everywhere">
            @error('country')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="sort_order">Sort order</label>
            <input value="{{ old('sort_order', $r->sort_order ?? 0) }}" type="number" min="0" name="sort_order"
                id="sort_order" class="form-input" placeholder="0">
            @error('sort_order')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $r->status ?? 1) == 1 ? 'selected' : '' }}>Enabled</option>
                <option value="0" {{ old('status', $r->status ?? 1) == 0 ? 'selected' : '' }}>Disabled</option>
            </select>
            @error('status')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.taxes.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
