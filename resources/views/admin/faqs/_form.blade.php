@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $f = $faq ?? null;
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="form-field col-span-2">
            <label for="question">Question <span class="text-red-500">*</span></label>
            <input value="{{ old('question', $f->question ?? '') }}" type="text" name="question" id="question"
                class="form-input" placeholder="e.g. How long does delivery take?" required>
            @error('question')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2">
            <label for="answer">Answer <span class="text-red-500">*</span></label>
            <textarea name="answer" id="answer" class="form-input" rows="5" required
                placeholder="The answer shown to customers">{{ old('answer', $f->answer ?? '') }}</textarea>
            @error('answer')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="category">Category</label>
            <input value="{{ old('category', $f->category ?? '') }}" type="text" name="category" id="category"
                class="form-input" list="faqCategories" placeholder="e.g. Shipping">
            <datalist id="faqCategories">
                @foreach ($categories ?? [] as $cat)
                    <option value="{{ $cat }}"></option>
                @endforeach
            </datalist>
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">Groups Q&A on the storefront FAQ page.</small>
            @error('category')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="sort_order">Sort order</label>
            <input value="{{ old('sort_order', $f->sort_order ?? 0) }}" type="number" min="0" name="sort_order"
                id="sort_order" class="form-input" placeholder="0">
            @error('sort_order')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $f->status ?? 1) == 1 ? 'selected' : '' }}>Enabled</option>
                <option value="0" {{ old('status', $f->status ?? 1) == 0 ? 'selected' : '' }}>Disabled</option>
            </select>
            @error('status')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.faqs.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
