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
            <label for="name">Category Name <span class="text-red-500">*</span></label>
            <input value="{{ old('name', $category->name ?? '') }}" type="text" name="name" id="name"
                class="form-input" placeholder="e.g. T-Shirt, Shoes, Hats" required>
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">The URL slug is generated automatically from
                the name.</small>
            @error('name')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>
         <div class="form-field col-span-2 md:col-span-1">
            <label for="sort_order">Sort Order</label>
            <input value="{{ old('sort_order', $category->sort_order ?? 0) }}" type="number" name="sort_order"
                id="sort_order" class="form-input" min="0" placeholder="e.g. 0, 1, 2">
            @error('sort_order')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>
        <div class="form-field col-span-2">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-input" rows="3"
                placeholder="Write something about this category...">{{ old('description', $category->description ?? '') }}</textarea>
            @error('description')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>
        <div class="form-field col-span-2 md:col-span-1">
            <label for="icon">Category Icon <span class="text-gray-400 font-normal">(Font Awesome
                    class)</span></label>
            <input value="{{ old('icon', $category->icon ?? '') }}" type="text" name="icon" id="icon"
                class="form-input" placeholder="e.g. fa-shirt">
            <small class="d-block mt-1">
                <a href="https://fontawesome.com/icons" target="_blank" class="text-blue-500 underline">Browse Font
                    Awesome</a>
                @if ($isEdit && !empty($category->icon))
                    <span class="ms-2 text-gray-500 dark:text-slate-400">Current: <i
                            class="fa-solid {{ $category->icon }} ml-1"></i></span>
                @endif
            </small>
            @error('icon')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>
        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $category->status ?? 1) == 1 ? 'selected' : '' }}>Enable
                </option>
                <option value="0" {{ old('status', $category->status ?? 1) == 0 ? 'selected' : '' }}>Disable
                </option>
            </select>
            @error('status')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>
        <div class="col-span-2 md:col-span-2">
            <x-image-upload name="image" label="Category Image" :value="$category->image ?? null"
                help="PNG, JPG, GIF or SVG — up to 2MB" />
        </div>
    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.categories.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
