@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $b = $banner ?? null;
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="form-field col-span-2">
            <x-image-upload name="image" label="Banner Image *"
                :value="$b && $b->image ? 'uploads/banners/' . $b->image : null"
                help="Wide/landscape image — JPG, PNG or WebP, up to 4MB" />
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="kicker">Kicker</label>
            <input value="{{ old('kicker', $b->kicker ?? '') }}" type="text" name="kicker" id="kicker"
                class="form-input" placeholder="e.g. New Season">
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">Small eyebrow text shown above the title.</small>
            @error('kicker')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="title">Title <span class="text-red-500">*</span></label>
            <input value="{{ old('title', $b->title ?? '') }}" type="text" name="title" id="title"
                class="form-input" placeholder="e.g. Summer Collection 2026" required>
            @error('title')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2">
            <label for="subtitle">Subtitle</label>
            <textarea name="subtitle" id="subtitle" class="form-input" rows="2"
                placeholder="Supporting copy shown under the title">{{ old('subtitle', $b->subtitle ?? '') }}</textarea>
            @error('subtitle')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="cta_text">Button text</label>
            <input value="{{ old('cta_text', $b->cta_text ?? '') }}" type="text" name="cta_text" id="cta_text"
                class="form-input" placeholder="e.g. Shop now">
            @error('cta_text')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="cta_link">Button link</label>
            <input value="{{ old('cta_link', $b->cta_link ?? '') }}" type="text" name="cta_link" id="cta_link"
                class="form-input" placeholder="e.g. /shop or https://…">
            @error('cta_link')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="sort_order">Sort order</label>
            <input value="{{ old('sort_order', $b->sort_order ?? 0) }}" type="number" min="0" name="sort_order"
                id="sort_order" class="form-input" placeholder="0">
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">Lower numbers show first.</small>
            @error('sort_order')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $b->status ?? 1) == 1 ? 'selected' : '' }}>Enabled</option>
                <option value="0" {{ old('status', $b->status ?? 1) == 0 ? 'selected' : '' }}>Disabled</option>
            </select>
            @error('status')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.banners.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
