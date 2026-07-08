@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'options' => [],
    'optionValue' => null,
    'optionLabel' => null,
    'value' => null,
    'placeholder' => 'Select…',
    'help' => null,
    'error' => null,
    'empty' => 'No results found',
    'searchable' => false,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'submitOnChange' => false,
    'loading' => false,
    'size' => 'md',
])

{{-- 
    <x-select> — Premium 2026 Shopify/Stripe-style custom select.

    A visually-hidden native <select> stays in the DOM as the single source of
    truth, so everything Laravel already relies on keeps working untouched:
      • form submission (GET + POST)      • old() repopulation
      • HTML validation / :required        • Alpine x-model passthrough
      • onchange handlers                  • GET filter values

    The visible Alpine UI reads the native <option>s and dispatches native
    change/input events on selection, so it is a drop-in for any <select>.

    All CSS + JS is inlined once per request (@once) — this component is fully
    self-contained and needs no edits to app.js or app.css.

    Usage:
        <x-select name="category_id" label="Category" :options="$categories"
            :value="old('category_id', $product->category_id ?? null)"
            placeholder="Select category" searchable />

        key => value / simple array:
        <x-select name="status" :options="['draft' => 'Draft', 'active' => 'Active']"
            :value="old('status')" />

        Inside an Alpine x-for repeater (passthrough attributes):
        <x-select :options="$sizeOptions" placeholder="Size"
            x-model="row.size_id" ::name="`variants[${i}][size_id]`" />
    --}}

@php
    // Resolve current value — old() wins when a field name is present.
    $current = $name ? old($name, $value) : $value;
    if (is_bool($current)) {
        $current = $current ? '1' : '0';
    }
    $current = is_null($current) ? '' : (string) $current;

    // Auto-pull the validation error straight from the bag (guarded for non-web renders).
    $resolvedError = $error ?? ($name && isset($errors) ? $errors->first($name) : null);

    // Field id for label association.
    $fieldId = $id ?? ($name ? $name . '_' . substr(md5($name . serialize($options)), 0, 5) : null);

    // Normalize any option shape into [ ['value' =>, 'label' =>, 'disabled' =>], ... ]
    $normalized = [];
    foreach ($options as $key => $option) {
        if (is_object($option)) {
            $optVal = data_get($option, $optionValue ?? 'id');
            $optLbl = data_get($option, $optionLabel ?? 'name');
            $optDis = (bool) data_get($option, 'disabled', false);
        } elseif (is_array($option)) {
            $optVal = $option[$optionValue ?? 'value'] ?? $option['value'] ?? $option['id'] ?? $key;
            $optLbl = $option[$optionLabel ?? 'label'] ?? $option['label'] ?? $option['name'] ?? $optVal;
            $optDis = (bool) ($option['disabled'] ?? false);
        } else {
            // Scalar list [v, v] => value == label; assoc [k => label] => value == k
            $optVal = is_int($key) ? $option : $key;
            $optLbl = $option;
            $optDis = false;
        }

        $normalized[] = [
            'value' => (string) $optVal,
            'label' => (string) $optLbl,
            'disabled' => $optDis,
        ];
    }
@endphp

@once
    <script>
        // Global factory so `x-data="customSelect(...)"` resolves without needing
        // alpine:init timing or any change to app.js.
        window.customSelect = function (config = {}) {
            return {
                open: false,
                search: '',
                activeIndex: -1,
                value: '',
                options: [],
                searchable: !!config.searchable,
                submitOnChange: !!config.submitOnChange,
                required: !!config.required,
                readonly: !!config.readonly,
                disabled: !!config.disabled,
                loading: !!config.loading,
                placeholder: config.placeholder ?? '',

                init() {
                    const native = this.$refs.native;
                    if (native.disabled) this.disabled = true;

                    this.refreshOptions();
                    this.value = native.value;
                    // Catch values seeded by Alpine x-model after this component inits.
                    this.$nextTick(() => { this.value = native.value; });

                    // Reflect external programmatic changes to the native <select>.
                    native.addEventListener('change', () => {
                        this.refreshOptions();
                        this.value = native.value;
                    });
                },

                refreshOptions() {
                    this.options = Array.from(this.$refs.native.options).map((o) => ({
                        value: o.value,
                        label: (o.textContent || '').trim(),
                        disabled: o.disabled,
                    }));
                },

                get filtered() {
                    if (!this.searchable || !this.search.trim()) return this.options;
                    const q = this.search.trim().toLowerCase();
                    return this.options.filter((o) => o.label.toLowerCase().includes(q));
                },

                get displayLabel() {
                    const o = this.options.find((o) => o.value === this.value);
                    return o && o.label !== '' ? o.label : this.placeholder;
                },

                get hasValue() {
                    return this.value !== '' && this.value !== null && this.value !== undefined;
                },

                get hasPlaceholder() {
                    return this.options.some((o) => o.value === '');
                },

                get interactive() {
                    return !this.disabled && !this.readonly && !this.loading;
                },

                toggle() {
                    if (!this.interactive) return;
                    this.open ? this.close() : this.openMenu();
                },

                openMenu() {
                    if (!this.interactive) return;
                    this.open = true;
                    this.search = '';
                    const idx = this.filtered.findIndex((o) => o.value === this.value);
                    this.activeIndex = idx >= 0 ? idx : 0;
                    this.$nextTick(() => {
                        if (this.searchable && this.$refs.search) this.$refs.search.focus();
                        this.scrollActive();
                    });
                },

                close() {
                    this.open = false;
                    this.activeIndex = -1;
                },

                clear() {
                    this.setValue('');
                },

                select(opt) {
                    if (!opt || opt.disabled) return;
                    this.setValue(opt.value);
                    this.close();
                    this.$refs.control && this.$refs.control.focus();
                },

                setValue(val) {
                    this.value = val;
                    const native = this.$refs.native;
                    native.value = val;
                    native.dispatchEvent(new Event('input', { bubbles: true }));
                    native.dispatchEvent(new Event('change', { bubbles: true }));
                    if (this.submitOnChange && native.form) {
                        typeof native.form.requestSubmit === 'function'
                            ? native.form.requestSubmit()
                            : native.form.submit();
                    }
                },

                onKey(e) {
                    if (!this.interactive) return;

                    if (!this.open) {
                        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) {
                            e.preventDefault();
                            this.openMenu();
                        }
                        return;
                    }

                    const list = this.filtered;
                    switch (e.key) {
                        case 'ArrowDown':
                            e.preventDefault();
                            if (list.length) { this.activeIndex = (this.activeIndex + 1) % list.length; this.scrollActive(); }
                            break;
                        case 'ArrowUp':
                            e.preventDefault();
                            if (list.length) { this.activeIndex = (this.activeIndex - 1 + list.length) % list.length; this.scrollActive(); }
                            break;
                        case 'Home':
                            e.preventDefault();
                            if (list.length) { this.activeIndex = 0; this.scrollActive(); }
                            break;
                        case 'End':
                            e.preventDefault();
                            if (list.length) { this.activeIndex = list.length - 1; this.scrollActive(); }
                            break;
                        case 'Enter':
                            e.preventDefault();
                            if (list[this.activeIndex]) this.select(list[this.activeIndex]);
                            break;
                        case 'Escape':
                            e.preventDefault();
                            this.close();
                            this.$refs.control && this.$refs.control.focus();
                            break;
                        case 'Tab':
                            this.close();
                            break;
                    }
                },

                scrollActive() {
                    this.$nextTick(() => {
                        const el = this.$refs.list && this.$refs.list.querySelector('[data-active]');
                        if (el) el.scrollIntoView({ block: 'nearest' });
                    });
                },
            };
        };
    </script>

    <style>
        /* ============================================================
           x-select — premium custom select
           Inherits the admin design tokens (--primary-color, --admin-line …)
           ============================================================ */
        .x-select { position: relative; width: 100%; --xs-h: 54px; --xs-radius: 14px; }
        .x-select--sm { --xs-h: 40px; --xs-radius: 11px; }

        /* Compact variant — control + dropdown both shrink. */
        .x-select--sm .x-select__control { font-size: 13px; padding: 0 10px 0 13px; }
        .x-select--sm .x-select__panel { border-radius: 12px; padding: 5px; }
        .x-select--sm .x-select__search { height: 34px; padding: 0 9px; }
        .x-select--sm .x-select__search input { font-size: 12.5px; }
        .x-select--sm .x-select__list { max-height: 300px; }
        .x-select--sm .x-select__option { padding: 9px 11px; font-size: 13px; border-radius: 9px; }
        .x-select--sm .x-select__caret { font-size: 11px; }

        .x-select__label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--admin-ink, #101827);
            margin-bottom: 8px;
        }
        .x-select__req { color: var(--danger-color, #dc2626); margin-left: 2px; }

        /* Visually hidden but still submitted / validated / x-model bound. */
        .x-select__native {
            position: absolute;
            width: 1px; height: 1px;
            padding: 0; margin: -1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
            border: 0;
        }

        .x-select__control {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            min-height: var(--xs-h);
            padding: 0 14px 0 16px;
            background: #ffffff;
            border: 1px solid var(--admin-line, #e5e7eb);
            border-radius: var(--xs-radius);
            color: var(--admin-ink, #101827);
            font-size: 14px;
            font-weight: 500;
            line-height: 1.3;
            text-align: left;
            cursor: pointer;
            transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
        }
        .x-select__control:hover { border-color: color-mix(in srgb, var(--primary-color, #101928) 45%, var(--admin-line, #e5e7eb)); }
        .x-select__control:focus-visible,
        .x-select.is-open .x-select__control {
            outline: none;
            border-color: var(--primary-color, #101928);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary-color, #101928) 18%, transparent);
        }

        .x-select__value {
            flex: 1 1 auto;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .x-select__value.is-placeholder { color: #98a2b3; font-weight: 400; }

        .x-select__indicators { display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; }

        .x-select__caret {
            font-size: 12px;
            color: #98a2b3;
            transition: transform .22s cubic-bezier(.16, 1, .3, 1);
        }
        .x-select.is-open .x-select__caret { transform: rotate(180deg); }

        .x-select__clear {
            display: grid; place-items: center;
            width: 20px; height: 20px;
            border: 0; border-radius: 50%;
            background: #f2f4f7; color: #667085;
            font-size: 10px; cursor: pointer;
            transition: background-color .15s ease, color .15s ease;
        }
        .x-select__clear:hover { background: #e4e7ec; color: #101827; }

        .x-select__spinner {
            width: 15px; height: 15px;
            border: 2px solid color-mix(in srgb, var(--primary-color, #101928) 25%, transparent);
            border-top-color: var(--primary-color, #101928);
            border-radius: 50%;
            animation: x-select-spin .6s linear infinite;
        }
        @keyframes x-select-spin { to { transform: rotate(360deg); } }

        /* States */
        .x-select.is-disabled .x-select__control { background: #f9fafb; color: #98a2b3; cursor: not-allowed; box-shadow: none; }
        .x-select.is-readonly .x-select__control { background: #f9fafb; cursor: default; }
        .x-select.has-error .x-select__control { border-color: var(--danger-color, #dc2626); }
        .x-select.has-error .x-select__control:focus-visible,
        .x-select.has-error.is-open .x-select__control { box-shadow: 0 0 0 4px color-mix(in srgb, var(--danger-color, #dc2626) 16%, transparent); }

        /* Dropdown panel */
        .x-select__panel {
            position: absolute;
            z-index: 60;
            top: calc(100% + 8px);
            left: 0; right: 0;
            min-width: 240px;
            background: #ffffff;
            border: 1px solid var(--admin-line, #e5e7eb);
            border-radius: 14px;
            box-shadow: 0 20px 44px rgba(16, 24, 40, 0.14), 0 2px 6px rgba(16, 24, 40, 0.06);
            padding: 6px;
            transform-origin: top center;
        }
        .x-select__panel--enter { transition: opacity .2s ease, transform .22s cubic-bezier(.16, 1, .3, 1); }
        .x-select__panel--leave { transition: opacity .15s ease, transform .15s ease; }
        .x-select__panel--from { opacity: 0; transform: translateY(-6px) scale(.97); }
        .x-select__panel--to   { opacity: 1; transform: translateY(0) scale(1); }

        .x-select__search {
            display: flex; align-items: center; gap: 8px;
            height: 38px;
            padding: 0 10px;
            margin-bottom: 6px;
            border-radius: 9px;
            background: #f4f6f9;
            border: 1px solid transparent;
            transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .x-select__search:focus-within {
            background: #ffffff;
            border-color: var(--primary-color, #101928);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-color, #101928) 14%, transparent);
        }
        .x-select__search i { color: #98a2b3; font-size: 12px; flex-shrink: 0; }
        .x-select__search:focus-within i { color: var(--primary-color, #101928); }
        .x-select__search input {
            flex: 1; min-width: 0;
            height: 100%; padding: 0;
            border: 0; background: transparent; outline: none;
            font-size: 13px; line-height: 1; color: var(--admin-ink, #101827);
        }
        .x-select__search input::placeholder { color: #98a2b3; }

        .x-select__list {
            list-style: none; margin: 0; padding: 0;
            max-height: 280px; overflow-y: auto;
            scrollbar-width: thin;
        }
        .x-select__list::-webkit-scrollbar { width: 8px; }
        .x-select__list::-webkit-scrollbar-thumb { background: #e4e7ec; border-radius: 8px; }

        .x-select__option {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 14px;
            color: var(--admin-ink, #101827);
            cursor: pointer;
            transition: background-color .12s ease, color .12s ease;
        }
        .x-select__option.is-active { background: color-mix(in srgb, var(--primary-color, #101928) 8%, #ffffff); }
        .x-select__option.is-selected { font-weight: 600; }
        .x-select__option.is-empty-opt { color: #667085; }
        .x-select__option.is-disabled { color: #cbd2dd; cursor: not-allowed; }
        .x-select__option-check { color: var(--primary-color, #101928); font-size: 12px; }

        .x-select__empty { padding: 16px 12px; text-align: center; color: #98a2b3; font-size: 13.5px; }

        .x-select__error { margin-top: 6px; font-size: 12.5px; color: var(--danger-color, #dc2626); }
        .x-select__help  { margin-top: 6px; font-size: 12.5px; color: #667085; }

        /* ---------- Dark mode ---------- */
        html.dark .x-select__label { color: #cdd6e6; }
        html.dark .x-select__control {
            background: #0e1830;
            border-color: rgba(255, 255, 255, 0.10);
            color: #e6ecf7;
        }
        html.dark .x-select__control:hover { border-color: rgba(255, 255, 255, 0.24); }
        html.dark .x-select.is-disabled .x-select__control,
        html.dark .x-select.is-readonly .x-select__control { background: #0b1226; color: #6b7a94; }
        html.dark .x-select__value.is-placeholder { color: #6b7a94; }
        html.dark .x-select__panel {
            background: #0e1830;
            border-color: rgba(255, 255, 255, 0.10);
            box-shadow: 0 24px 50px rgba(0, 0, 0, 0.5);
        }
        html.dark .x-select__search { background: rgba(255, 255, 255, 0.05); border-color: transparent; }
        html.dark .x-select__search:focus-within {
            background: #0b1226;
            border-color: var(--primary-color, #101928);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-color, #101928) 30%, transparent);
        }
        html.dark .x-select__search input { color: #e6ecf7; }
        html.dark .x-select__search input::placeholder { color: #6b7a94; }
        html.dark .x-select__option { color: #cdd6e6; }
        html.dark .x-select__option.is-active { background: rgba(255, 255, 255, 0.06); }
        html.dark .x-select__clear { background: rgba(255, 255, 255, 0.08); color: #9fb0c9; }
        html.dark .x-select__list::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.14); }

        [x-cloak] { display: none !important; }
    </style>
@endonce

<div
    {{ $attributes->only('class')->merge(['class' => 'x-select' . ($size === 'sm' ? ' x-select--sm' : '')]) }}
    x-data="customSelect({
        searchable: {{ $searchable ? 'true' : 'false' }},
        submitOnChange: {{ $submitOnChange ? 'true' : 'false' }},
        required: {{ $required ? 'true' : 'false' }},
        disabled: {{ $disabled ? 'true' : 'false' }},
        readonly: {{ $readonly ? 'true' : 'false' }},
        loading: {{ $loading ? 'true' : 'false' }},
        placeholder: @js($placeholder ?? ''),
    })"
    @keydown="onKey($event)"
    @click.outside="close()"
    :class="{ 'is-open': open, 'is-disabled': disabled || loading, 'is-readonly': readonly, 'has-value': hasValue }"
    @class(['has-error' => $resolvedError])
>
    @if ($label)
        <label class="x-select__label" @if ($fieldId) for="{{ $fieldId }}" @endif>
            {{ $label }}@if ($required)<span class="x-select__req">*</span>@endif
        </label>
    @endif

    {{-- Hidden native <select>: the single source of truth for the form. --}}
    <select
        x-ref="native"
        @if ($fieldId) id="{{ $fieldId }}" @endif
        @if ($name) name="{{ $name }}" @endif
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        tabindex="-1"
        aria-hidden="true"
        {{ $attributes->except('class')->merge(['class' => 'x-select__native']) }}
    >
        @if (! is_null($placeholder))
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($normalized as $opt)
            <option value="{{ $opt['value'] }}" @selected($current === $opt['value']) @disabled($opt['disabled'])>{{ $opt['label'] }}</option>
        @endforeach
        {{ $slot }}
    </select>

    {{-- Visible control --}}
    <button
        type="button"
        x-ref="control"
        class="x-select__control"
        @click="toggle()"
        :disabled="disabled || loading"
        :aria-expanded="open ? 'true' : 'false'"
        aria-haspopup="listbox"
        role="combobox"
    >
        <span class="x-select__value" :class="{ 'is-placeholder': !hasValue }" x-text="displayLabel"></span>

        <span class="x-select__indicators">
            <template x-if="loading">
                <span class="x-select__spinner" aria-hidden="true"></span>
            </template>
            <template x-if="!loading && hasValue && interactive && !required && hasPlaceholder">
                <span class="x-select__clear" @click.stop="clear()" role="button" tabindex="-1" aria-label="Clear selection">
                    <i class="fa-solid fa-xmark"></i>
                </span>
            </template>
            <i class="fa-solid fa-chevron-down x-select__caret" aria-hidden="true"></i>
        </span>
    </button>

    {{-- Animated dropdown --}}
    <div
        class="x-select__panel"
        x-show="open"
        x-cloak
        x-transition:enter="x-select__panel--enter"
        x-transition:enter-start="x-select__panel--from"
        x-transition:enter-end="x-select__panel--to"
        x-transition:leave="x-select__panel--leave"
        x-transition:leave-start="x-select__panel--to"
        x-transition:leave-end="x-select__panel--from"
        role="listbox"
    >
        <template x-if="searchable">
            <div class="x-select__search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" x-ref="search" x-model="search" placeholder="Search…"
                    @keydown.stop="onKey($event)" @click.stop autocomplete="off">
            </div>
        </template>

        <ul class="x-select__list" x-ref="list">
            <template x-for="(opt, i) in filtered" :key="opt.value + '::' + i">
                <li
                    class="x-select__option"
                    :class="{ 'is-active': i === activeIndex, 'is-selected': opt.value === value, 'is-disabled': opt.disabled, 'is-empty-opt': opt.value === '' }"
                    :data-active="i === activeIndex ? true : null"
                    role="option"
                    :aria-selected="opt.value === value ? 'true' : 'false'"
                    @click="select(opt)"
                    @mouseenter="activeIndex = i"
                >
                    <span class="x-select__option-label" x-text="opt.label"></span>
                    <i class="fa-solid fa-check x-select__option-check" x-show="opt.value === value && opt.value !== ''"></i>
                </li>
            </template>

            <li class="x-select__empty" x-show="filtered.length === 0">{{ $empty }}</li>
        </ul>
    </div>

    @if ($resolvedError)
        <p class="x-select__error">{{ $resolvedError }}</p>
    @elseif ($help)
        <p class="x-select__help">{{ $help }}</p>
    @endif
</div>
