@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $p = $page ?? null;
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 gap-4">

        <div class="form-field">
            <label for="title">Title <span class="text-red-500">*</span></label>
            <input value="{{ old('title', $p->title ?? '') }}" type="text" name="title" id="title"
                class="form-input" placeholder="{{ __('e.g. About Us') }}" required>
            @if ($isEdit)
                <small class="text-gray-400 dark:text-slate-500 d-block mt-1">URL slug: <span class="font-mono">/{{ $p->slug }}</span></small>
            @else
                <small class="text-gray-400 dark:text-slate-500 d-block mt-1">{{ __('The URL slug is generated automatically.') }}</small>
            @endif
            @error('title')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label for="content">{{ __('Content') }}</label>
            <textarea name="content" id="content" class="form-input" rows="12"
                placeholder="{{ __('Page body — plain text or HTML') }}" style="font-family: ui-monospace, monospace; line-height: 1.6;">{{ old('content', $p->content ?? '') }}</textarea>
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">{{ __('Supports HTML. Rendered on the storefront page.') }}</small>
            @error('content')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-field">
                <label for="seo_title">{{ __('SEO title') }}</label>
                <input value="{{ old('seo_title', $p->seo_title ?? '') }}" type="text" name="seo_title" id="seo_title"
                    class="form-input" placeholder="{{ __('Defaults to the page title') }}">
                @error('seo_title')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label for="status">{{ __('Status') }}</label>
                <select name="status" id="status" class="form-input">
                    <option value="1" {{ old('status', $p->status ?? 1) == 1 ? 'selected' : '' }}>{{ __('Published') }}</option>
                    <option value="0" {{ old('status', $p->status ?? 1) == 0 ? 'selected' : '' }}>{{ __('Draft') }}</option>
                </select>
                @error('status')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="form-field">
            <label for="seo_description">{{ __('SEO description') }}</label>
            <textarea name="seo_description" id="seo_description" class="form-input" rows="2"
                placeholder="{{ __('Short meta description for search engines') }}">{{ old('seo_description', $p->seo_description ?? '') }}</textarea>
            @error('seo_description')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.pages.index') }}" class="form-cancel-button">{{ __('Cancel') }}</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
