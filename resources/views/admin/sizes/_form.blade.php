@php
    $isEdit = ($mode ?? 'create') === 'edit';
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <div class="form-field col-span-2 md:col-span-1">
            <label for="name">Size Name <span class="text-red-500">*</span></label>
            <input value="{{ old('name', $size->name ?? '') }}" type="text" name="name" id="name"
                class="form-input" placeholder="e.g. Small, Medium, Large" required>
            @error('name')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="code">Size Code <span class="text-red-500">*</span></label>
            <input value="{{ old('code', $size->code ?? '') }}" type="text" name="code" id="code"
                class="form-input" placeholder="e.g. S, M, L, XL" required>
            @error('code')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="sort_order">Sort Order</label>
            <input value="{{ old('sort_order', $size->sort_order ?? 0) }}" type="number" name="sort_order" id="sort_order"
                class="form-input" min="0" placeholder="e.g. 0, 1, 2">
            @error('sort_order')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $size->status ?? 1) == 1 ? 'selected' : '' }}>Enable</option>
                <option value="0" {{ old('status', $size->status ?? 1) == 0 ? 'selected' : '' }}>Disable</option>
            </select>
            @error('status')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.sizes.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>