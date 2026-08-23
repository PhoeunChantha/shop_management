@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $locales = $locales ?? ['en' => 'English'];
    $primaryLang = $primaryLang ?? array_key_first($locales);

    // Current value of a translatable field for one language (old() wins).
    $t = fn (string $field, string $code) => old("$field.$code", $isEdit ? ($category->getTranslation($field, $code, false) ?: '') : '');
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="category-form" data-category-form>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body category-form__body" x-data="{ lang: @js($primaryLang) }">

        {{-- Language tabs — drive every translatable field (Settings → Languages) --}}
        @if (count($locales) > 1)
            <div class="lang-tabs category-form__langs">
                <span class="lang-tabs__label">{{ __('Content language') }}</span>
                <div class="lang-tabs__buttons">
                    @foreach ($locales as $code => $label)
                        <button type="button" class="lang-tab" :class="{ 'is-active': lang === @js($code) }" @click="lang = @js($code)">
                            <span>{{ $label }}</span>
                            <small>{{ strtoupper($code) }}</small>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ============================ DETAILS ============================ --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Name (per language) --}}
            @foreach ($locales as $code => $label)
                <div class="form-field col-span-2 md:col-span-1" x-show="lang === @js($code)" x-cloak>
                    <label for="name_{{ $code }}">
                        {{ __('Category Name') }} <span class="lang-badge">{{ strtoupper($code) }}</span>
                        @if ($code === $primaryLang)<span class="text-red-500">*</span>@endif
                    </label>
                    <input type="text" name="name[{{ $code }}]" id="name_{{ $code }}" class="form-input"
                        value="{{ $t('name', $code) }}" @if ($code === $primaryLang) required @endif
                        maxlength="255" placeholder="{{ __('e.g. T-Shirt, Shoes, Hats') }}"
                        data-seo-source="title" data-lang="{{ $code }}">
                    @if ($code === $primaryLang)
                        <small class="text-gray-400 dark:text-slate-500 d-block mt-1">{{ __('The URL slug is generated automatically from the name.') }}</small>
                    @endif
                    @error("name.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>
            @endforeach
            @error('name')<p class="text-red-500 text-sm -mt-2 col-span-2">{{ $message }}</p>@enderror

            <div class="form-field col-span-2 md:col-span-1">
                <label for="sort_order">{{ __('Sort Order') }}</label>
                <input value="{{ old('sort_order', $category->sort_order ?? 0) }}" type="number" name="sort_order"
                    id="sort_order" class="form-input" min="0" placeholder="{{ __('e.g. 0, 1, 2') }}">
                @error('sort_order')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
            </div>

            {{-- Description (per language) --}}
            @foreach ($locales as $code => $label)
                <div class="form-field col-span-2" x-show="lang === @js($code)" x-cloak>
                    <label for="description_{{ $code }}">{{ __('Description') }} <span class="lang-badge">{{ strtoupper($code) }}</span></label>
                    <textarea name="description[{{ $code }}]" id="description_{{ $code }}" class="form-input" rows="3"
                        placeholder="{{ __('Write something about this category...') }}"
                        data-seo-source="description" data-lang="{{ $code }}">{{ $t('description', $code) }}</textarea>
                    @error("description.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>
            @endforeach

            <div class="form-field col-span-2 md:col-span-1">
                <label for="icon">{{ __('Category Icon') }} <span class="text-gray-400 font-normal">{{ __('(Font Awesome class)') }}</span></label>
                <input value="{{ old('icon', $category->icon ?? '') }}" type="text" name="icon" id="icon"
                    class="form-input" placeholder="{{ __('e.g. fa-shirt') }}">
                <small class="d-block mt-1">
                    <a href="https://fontawesome.com/icons" target="_blank" rel="noopener" class="text-blue-500 underline">{{ __('Browse Font Awesome') }}</a>
                    @if ($isEdit && !empty($category->icon))
                        <span class="ms-2 text-gray-500 dark:text-slate-400">{{ __('Current') }}: <i class="fa-solid {{ $category->icon }} ml-1"></i></span>
                    @endif
                </small>
                @error('icon')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
            </div>

            <div class="form-field col-span-2 md:col-span-1">
                <x-select name="status" size="sm" :label="__('Status')" :value="old('status', (string) (int) ($category->status ?? 1))" placeholder=""
                    :options="['1' => __('Enable'), '0' => __('Disable')]" required />
                @error('status')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
            </div>

            <div class="col-span-2">
                <x-image-upload name="image" label="{{ __('Category Image') }}" folder="categories" :value="$category->image ?? null"
                    help="{{ __('PNG, JPG, GIF or SVG — up to 2MB') }}" />
            </div>
        </div>

        {{-- ============================ SEO ============================ --}}
        <section class="category-seo">
            <header class="category-seo__head">
                <div>
                    <h4>{{ __('Search engine optimisation') }}</h4>
                    <p>{{ __('Title and description are pre-filled from the category name and description until you edit them yourself.') }}</p>
                </div>
                <button type="button" class="btn-flat" data-seo-reset>
                    <i class="fa-solid fa-rotate-left"></i><span>{{ __('Re-sync from content') }}</span>
                </button>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- SEO title (per language) --}}
                @foreach ($locales as $code => $label)
                    <div class="form-field col-span-2 md:col-span-1" x-show="lang === @js($code)" x-cloak>
                        <label for="seo_title_{{ $code }}">
                            {{ __('SEO Title') }} <span class="lang-badge">{{ strtoupper($code) }}</span>
                            <span class="seo-count" data-seo-count-for="seo_title_{{ $code }}" data-max="60"></span>
                        </label>
                        <input type="text" name="seo_title[{{ $code }}]" id="seo_title_{{ $code }}" class="form-input" maxlength="255"
                            value="{{ $t('seo_title', $code) }}" placeholder="{{ __('Defaults to the category name') }}"
                            data-seo-target="title" data-lang="{{ $code }}">
                        <p class="form-help">{{ __('Shown as the page title in search results. Around 60 characters is ideal.') }}</p>
                        @error("seo_title.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                {{-- SEO keywords (per language) --}}
                @foreach ($locales as $code => $label)
                    <div class="form-field col-span-2 md:col-span-1" x-show="lang === @js($code)" x-cloak>
                        <label for="seo_keywords_{{ $code }}">{{ __('SEO Keywords') }} <span class="lang-badge">{{ strtoupper($code) }}</span></label>
                        <input type="text" name="seo_keywords[{{ $code }}]" id="seo_keywords_{{ $code }}" class="form-input" maxlength="500"
                            value="{{ $t('seo_keywords', $code) }}" placeholder="{{ __('e.g. t-shirts, cotton tees, streetwear') }}">
                        <p class="form-help">{{ __('Comma-separated. Optional — most search engines ignore it, but it is kept for completeness.') }}</p>
                        @error("seo_keywords.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                {{-- SEO description (per language) --}}
                @foreach ($locales as $code => $label)
                    <div class="form-field col-span-2" x-show="lang === @js($code)" x-cloak>
                        <label for="seo_description_{{ $code }}">
                            {{ __('SEO Description') }} <span class="lang-badge">{{ strtoupper($code) }}</span>
                            <span class="seo-count" data-seo-count-for="seo_description_{{ $code }}" data-max="160"></span>
                        </label>
                        <textarea name="seo_description[{{ $code }}]" id="seo_description_{{ $code }}" class="form-input" rows="2" maxlength="500"
                            placeholder="{{ __('Defaults to the category description') }}"
                            data-seo-target="description" data-lang="{{ $code }}">{{ $t('seo_description', $code) }}</textarea>
                        <p class="form-help">{{ __('The snippet under the title in search results. Around 160 characters is ideal.') }}</p>
                        @error("seo_description.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                <div class="col-span-2">
                    <x-image-upload name="seo_image" label="{{ __('SEO / social image') }}" folder="categories" :value="$category->seo_image ?? null"
                        help="{{ __('Used for Open Graph / social previews. 1200×630 recommended, up to 2MB.') }}" />
                </div>

                {{-- Search preview (primary language) --}}
                <div class="col-span-2 seo-preview" data-seo-preview data-lang="{{ $primaryLang }}">
                    <span class="seo-preview__label">{{ __('Search preview') }}</span>
                    <div class="seo-preview__card">
                        <div class="seo-preview__url">{{ rtrim(config('app.url'), '/') }}/shop?category=<span data-seo-preview-slug>{{ $isEdit ? $category->slug : __('category-slug') }}</span></div>
                        <div class="seo-preview__title" data-seo-preview-title>{{ $t('seo_title', $primaryLang) ?: ($t('name', $primaryLang) ?: __('Category title')) }}</div>
                        <div class="seo-preview__desc" data-seo-preview-desc>{{ \Illuminate\Support\Str::limit($t('seo_description', $primaryLang) ?: ($t('description', $primaryLang) ?: __('A short description of what shoppers will find in this category.')), 160) }}</div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.categories.index') }}" class="form-cancel-button">{{ __('Cancel') }}</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>

@once
@push('js')
<script>
    // Category form: auto-fill SEO title/description from name/description (per
    // language) until the admin edits the SEO field by hand; live counters and a
    // search-result preview for the primary language.
    (function () {
        const form = document.querySelector('[data-category-form]');
        if (!form) return;

        const $ = (sel, root = form) => Array.from(root.querySelectorAll(sel));
        const source = (kind, lang) => form.querySelector(`[data-seo-source="${kind}"][data-lang="${lang}"]`);
        const target = (kind, lang) => form.querySelector(`[data-seo-target="${kind}"][data-lang="${lang}"]`);
        const langs = [...new Set($('[data-seo-source]').map((el) => el.dataset.lang))];
        const slugify = (s) => s.toLowerCase().normalize('NFKD').replace(/[^\w\s-]/g, '').trim().replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '');

        // A target is "auto" while it is empty or still equals the derived value.
        const derive = (kind, lang) => {
            const src = source(kind, lang);
            if (!src) return '';
            const v = src.value.trim();
            return kind === 'description' ? v.slice(0, 160) : v;
        };
        const isAuto = (kind, lang) => {
            const t = target(kind, lang);
            return t && (t.value.trim() === '' || t.value.trim() === derive(kind, lang) || t.dataset.auto === '1');
        };

        // Seed auto flags on load: empty or identical-to-source fields follow the source.
        langs.forEach((lang) => ['title', 'description'].forEach((kind) => {
            const t = target(kind, lang);
            if (t && isAuto(kind, lang)) t.dataset.auto = '1';
        }));

        const sync = (kind, lang) => {
            const t = target(kind, lang);
            if (t && t.dataset.auto === '1') { t.value = derive(kind, lang); counts(); preview(); }
        };

        $('[data-seo-source]').forEach((el) => el.addEventListener('input', () => {
            sync(el.dataset.seoSource, el.dataset.lang);
            preview();
        }));

        // Typing in a SEO field detaches it from the source; clearing it re-attaches.
        $('[data-seo-target]').forEach((el) => el.addEventListener('input', () => {
            el.dataset.auto = el.value.trim() === '' ? '1' : '0';
            if (el.dataset.auto === '1') sync(el.dataset.seoTarget, el.dataset.lang);
            counts(); preview();
        }));

        form.querySelector('[data-seo-reset]')?.addEventListener('click', () => {
            langs.forEach((lang) => ['title', 'description'].forEach((kind) => {
                const t = target(kind, lang);
                if (t) { t.dataset.auto = '1'; t.value = derive(kind, lang); }
            }));
            counts(); preview();
        });

        function counts() {
            $('[data-seo-count-for]').forEach((c) => {
                const el = document.getElementById(c.dataset.seoCountFor);
                if (!el) return;
                const n = el.value.length, max = parseInt(c.dataset.max, 10);
                c.textContent = `${n} / ${max}`;
                c.classList.toggle('is-over', n > max);
            });
        }

        function preview() {
            const box = form.querySelector('[data-seo-preview]');
            if (!box) return;
            const lang = box.dataset.lang;
            const name = source('title', lang)?.value.trim() || '';
            const title = target('title', lang)?.value.trim() || name;
            const desc = target('description', lang)?.value.trim() || source('description', lang)?.value.trim() || '';
            const t = box.querySelector('[data-seo-preview-title]'), d = box.querySelector('[data-seo-preview-desc]'), s = box.querySelector('[data-seo-preview-slug]');
            if (title) t.textContent = title;
            if (desc) d.textContent = desc.slice(0, 160);
            if (s && name && !s.dataset.locked) s.textContent = slugify(name) || s.textContent;
        }

        // Native validation can't focus a field hidden behind another language tab:
        // jump to that tab first so the browser's message lands on a visible input.
        form.addEventListener('invalid', (e) => {
            const lang = e.target?.dataset?.lang;
            const body = form.querySelector('[x-data]');
            if (lang && body && window.Alpine) {
                try { window.Alpine.$data(body).lang = lang; } catch (err) {}
            }
        }, true);

        counts(); preview();
    })();
</script>
@endpush
@endonce
