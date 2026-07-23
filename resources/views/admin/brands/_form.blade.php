@php
    $isEdit = ($mode ?? 'create') === 'edit';
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="form-field col-span-2 md:col-span-1">
            <label for="name">Brand Name <span class="text-red-500">*</span></label>
            <input value="{{ old('name', $brand->name ?? '') }}" type="text" name="name" id="name"
                class="form-input" placeholder="e.g. Nike, Adidas, Atelier" required>
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">The URL slug is generated automatically from
                the name.</small>
            @error('name')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $brand->status ?? 1) == 1 ? 'selected' : '' }}>Enable</option>
                <option value="0" {{ old('status', $brand->status ?? 1) == 0 ? 'selected' : '' }}>Disable</option>
            </select>
            @error('status')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="col-span-2 md:col-span-2">
            <x-image-upload name="image" label="Brand Logo" folder="brands" :value="$brand->image ?? null"
                help="PNG, JPG, GIF, SVG or WEBP — up to 2MB" />
        </div>
    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.brands.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
