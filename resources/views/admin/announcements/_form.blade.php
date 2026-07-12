@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $a = $announcement ?? null;
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="form-field col-span-2">
            <label for="message">Message <span class="text-red-500">*</span></label>
            <input value="{{ old('message', $a->message ?? '') }}" type="text" name="message" id="message"
                class="form-input" maxlength="255" placeholder="e.g. Free shipping on orders over $50 🎉" required>
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">Shown in the storefront top bar.</small>
            @error('message')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2">
            <label for="link">Link</label>
            <input value="{{ old('link', $a->link ?? '') }}" type="text" name="link" id="link"
                class="form-input" placeholder="e.g. /shop or https://…">
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">Optional — makes the whole bar clickable.</small>
            @error('link')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="sort_order">Sort order</label>
            <input value="{{ old('sort_order', $a->sort_order ?? 0) }}" type="number" min="0" name="sort_order"
                id="sort_order" class="form-input" placeholder="0">
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">Lower numbers rotate first.</small>
            @error('sort_order')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $a->status ?? 1) == 1 ? 'selected' : '' }}>Enabled</option>
                <option value="0" {{ old('status', $a->status ?? 1) == 0 ? 'selected' : '' }}>Disabled</option>
            </select>
            @error('status')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.announcements.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
