@php
    $isEdit = ($mode ?? 'create') === 'edit';
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-2 gap-4">
        
        {{-- Color Name Input --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="name">Color Name <span class="text-red-500">*</span></label>
            <input value="{{ old('name', $color->name ?? '') }}" type="text" name="name" id="name"
                class="form-input" placeholder="e.g. Red, Black, Royal Blue" required>
            @error('name')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Color Code Input (Hex Code) --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="code">Color Code (Hex) <span class="text-red-500">*</span></label>
            <div class="flex gap-2">
                {{-- ទម្រង់ Color Picker ងាយស្រួលចុចរើសពណ៌ --}}
                <input type="color" value="{{ old('code', $color->code ?? '#000000') }}" 
                    oninput="document.getElementById('code').value = this.value"
                    class="w-11 h-11 p-0.5 border border-gray-300 rounded cursor-pointer">
                
                <input value="{{ old('code', $color->code ?? '#000000') }}" type="text" name="code" id="code"
                    class="form-input font-mono" placeholder="e.g. #FF0000, #000000" required>
            </div>
            @error('code')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Sort Order Input --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="sort_order">Sort Order</label>
            <input value="{{ old('sort_order', $color->sort_order ?? 0) }}" type="number" name="sort_order" id="sort_order"
                class="form-input" min="0" placeholder="e.g. 0, 1, 2">
            @error('sort_order')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Status Input --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $color->status ?? 1) == 1 ? 'selected' : '' }}>Enable</option>
                <option value="0" {{ old('status', $color->status ?? 1) == 0 ? 'selected' : '' }}>Disable</option>
            </select>
            @error('status')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.colors.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>