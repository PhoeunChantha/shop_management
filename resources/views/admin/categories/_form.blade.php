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
            @error('name')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="slug">Slug (Optional)</label>
            <input value="{{ old('slug', $category->slug ?? '') }}" type="text" name="slug" id="slug"
                class="form-input" placeholder="e.g. t-shirt, shoes-collection">
            <small class="text-gray-400 d-block mt-1"></small>
            @error('slug')
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
            <label for="image">Category Image</label>
            <input type="file" name="image" id="image" class="form-input" accept="image/*">
            
            @if($isEdit && !empty($category->image))
                <div class="mt-2">
                    <p class="text-xs text-gray-400 mb-1">Current Image:</p>
                    <img src="{{ asset($category->image) }}" alt="Category Image" class="w-20 h-20 object-cover rounded border">
                </div>
            @endif
            @error('image')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="icon">Category Icon (Font Awesome Class)</label>
            <input value="{{ old('icon', $category->icon ?? '') }}" type="text" name="icon" id="icon"
                class="form-input" placeholder="e.g. fa-shirt">
            <small class="text-gray-400 d-block mt-1"><a href="https://fontawesome.com/icons" target="_blank" class="text-blue-500 underline">Font Awesome</a></small>
            
            @if($isEdit && !empty($category->icon))
                <div class="mt-2 text-sm text-gray-500">
                    <span>Current Icon: </span>
                    <i class="fa-solid {{ $category->icon }} text-xl ml-1"></i>
                </div>
            @endif
            @error('icon')2
            456+
            
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="sort_order">Sort Order</label>
            <input value="{{ old('sort_order', $category->sort_order ?? 0) }}" type="number" name="sort_order" id="sort_order"
                class="form-input" min="0" placeholder="e.g. 0, 1, 2">
            @error('sort_order')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $category->status ?? 1) == 1 ? 'selected' : '' }}>Enable</option>
                <option value="0" {{ old('status', $category->status ?? 1) == 0 ? 'selected' : '' }}>Disable</option>
            </select>
            @error('status')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
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