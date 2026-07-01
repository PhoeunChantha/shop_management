<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Configuration</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Settings') }}
            </h2>
        </div>
    </x-slot>

    @php
        // Open the tab that contains the first validation error, otherwise General.
        $activeTab = array_key_first($schema);
        foreach ($schema as $groupKey => $group) {
            foreach ($group['fields'] ?? [] as $fieldKey => $field) {
                if ($errors->has($fieldKey)) {
                    $activeTab = $groupKey;
                    break 2;
                }
            }
        }
        if ($errors->has('social_links') || collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'social_links'))) {
            $activeTab = 'social';
        }
    @endphp

    <div class="" x-data="{ tab: '{{ $activeTab }}' }">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Site setup</p>
                <h3>Manage settings</h3>
            </div>
        </div>

        <section class="premium-card form-panel settings-layout">
            <aside class="settings-tabs">
                @foreach ($schema as $groupKey => $group)
                    <button type="button" class="settings-tab" :class="{ 'is-active': tab === '{{ $groupKey }}' }"
                        @click="tab = '{{ $groupKey }}'">
                        <i class="fa-solid {{ $group['icon'] }}"></i>
                        <span>{{ $group['label'] }}</span>
                    </button>
                @endforeach
            </aside>

            <div class="settings-content">
                <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @foreach ($schema as $groupKey => $group)
                        @if ($groupKey === 'appearance')
                            @php
                                $colorDefaults = [];
                                $colorState = [];
                                foreach ($group['fields'] as $fk => $f) {
                                    $colorDefaults[$fk] = $f['default'] ?? '#000000';
                                    $colorState[$fk] = old($fk, $values[$fk] ?? ($f['default'] ?? '#000000'));
                                }
                            @endphp
                            <div class="form-panel-body grid-cols-1 sm:grid-cols-2 gap-x-5"
                                x-show="tab === '{{ $groupKey }}'" x-cloak
                                x-data="{
                                    colors: @js($colorState),
                                    defaults: @js($colorDefaults),
                                    reset() { this.colors = Object.assign({}, this.defaults); }
                                }">
                                @foreach ($group['fields'] as $fieldKey => $field)
                                    <div class="form-field">
                                        <label for="{{ $fieldKey }}">{{ $field['label'] }}</label>
                                        <div class="color-field">
                                            <input type="color" class="color-field__picker"
                                                x-model="colors['{{ $fieldKey }}']"
                                                aria-label="{{ $field['label'] }} color picker">
                                            <input type="text" name="{{ $fieldKey }}" id="{{ $fieldKey }}"
                                                class="form-input color-field__hex" x-model="colors['{{ $fieldKey }}']"
                                                maxlength="7" spellcheck="false" placeholder="#000000">
                                        </div>
                                        @if (!empty($field['hint']))
                                            <p class="color-field__hint">{{ $field['hint'] }}</p>
                                        @endif
                                        @error($fieldKey)
                                            <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach

                                <div class="sm:col-span-2 color-reset-row">
                                    <button type="button" class="form-cancel-button" @click="reset()">
                                        <i class="fa-solid fa-rotate-left"></i> Reset to defaults
                                    </button>
                                    <span class="color-reset-hint">Resets the colors above — click “Save changes” to apply.</span>
                                </div>
                            </div>
                        @else
                        <div class="form-panel-body {{ ($group['type'] ?? 'fields') === 'fields' ? 'grid-cols-1 sm:grid-cols-2 gap-x-5' : '' }}"
                            x-show="tab === '{{ $groupKey }}'" x-cloak>
                            @if (($group['type'] ?? 'fields') === 'fields')
                                @foreach ($group['fields'] as $fieldKey => $field)
                                    {{-- Image fields are grouped into their own row below. --}}
                                    @continue(($field['type'] ?? 'text') === 'image')

                                    {{-- Multiselect (Alpine chips picker): stores an array of keys --}}
                                    @if (($field['type'] ?? 'text') === 'multiselect')
                                        @php($selectedVals = old($fieldKey, json_decode($values[$fieldKey] ?? '[]', true) ?: ($field['default'] ?? [])))
                                        <div class="form-field sm:col-span-2"
                                            x-data="{
                                                open: false,
                                                options: @js($field['options'] ?? []),
                                                selected: @js(array_values((array) $selectedVals)),
                                                toggle(code) { this.selected.includes(code) ? (this.selected = this.selected.filter(c => c !== code)) : this.selected.push(code); },
                                                remove(code) { this.selected = this.selected.filter(c => c !== code); },
                                                label(code) { return this.options[code] ?? code; }
                                            }"
                                            @click.outside="open = false">
                                            <label>{{ $field['label'] }}</label>

                                            <div class="ms-select" :class="{ 'is-open': open }" @click="open = !open">
                                                <div class="ms-select__tags">
                                                    <template x-for="code in selected" :key="code">
                                                        <span class="ms-tag" @click.stop>
                                                            <span x-text="label(code)"></span>
                                                            <button type="button" @click="remove(code)" aria-label="Remove">&times;</button>
                                                        </span>
                                                    </template>
                                                    <span class="ms-select__placeholder" x-show="selected.length === 0">Select languages…</span>
                                                </div>
                                                <i class="fa-solid fa-chevron-down ms-select__caret"></i>
                                            </div>

                                            <div class="ms-select__menu" x-show="open" x-cloak @click.stop>
                                                <template x-for="(lbl, code) in options" :key="code">
                                                    <button type="button" class="ms-option" :class="{ 'is-selected': selected.includes(code) }" @click="toggle(code)">
                                                        <span x-text="lbl"></span>
                                                        <i class="fa-solid fa-check" x-show="selected.includes(code)"></i>
                                                    </button>
                                                </template>
                                            </div>

                                            <template x-for="code in selected" :key="'hidden-' + code">
                                                <input type="hidden" name="{{ $fieldKey }}[]" :value="code">
                                            </template>

                                            @if (!empty($field['hint']))
                                                <p class="color-field__hint">{{ $field['hint'] }}</p>
                                            @endif
                                            @error($fieldKey)<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                                        </div>
                                        @continue
                                    @endif

                                    <div class="form-field {{ ($field['type'] ?? 'text') === 'textarea' ? 'sm:col-span-2' : '' }}">
                                        <label for="{{ $fieldKey }}">{{ $field['label'] }}</label>

                                        @if (($field['type'] ?? 'text') === 'textarea')
                                            <textarea name="{{ $fieldKey }}" id="{{ $fieldKey }}" rows="3"
                                                class="form-input" placeholder="{{ $field['placeholder'] ?? '' }}">{{ old($fieldKey, $values[$fieldKey] ?? '') }}</textarea>
                                        @else
                                            <input type="{{ $field['type'] ?? 'text' }}" name="{{ $fieldKey }}" id="{{ $fieldKey }}"
                                                value="{{ old($fieldKey, $values[$fieldKey] ?? '') }}"
                                                class="form-input" placeholder="{{ $field['placeholder'] ?? '' }}">
                                        @endif

                                        @error($fieldKey)
                                            <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach

                                @php($imageFields = collect($group['fields'])->filter(fn ($f) => ($f['type'] ?? '') === 'image'))
                                @if ($imageFields->isNotEmpty())
                                    <div class="sm:col-span-2 settings-upload-row">
                                        @foreach ($imageFields as $fieldKey => $field)
                                            <x-image-upload :name="$fieldKey" :label="$field['label']"
                                                :value="\App\Helpers\ImageManager::path($values[$fieldKey] ?? null, $field['folder'] ?? 'settings')"
                                                :accept="$field['accept'] ?? 'image/*'"
                                                :help="$field['help'] ?? 'PNG, JPG, GIF or SVG — up to 2MB'" />
                                        @endforeach
                                    </div>
                                @endif
                            @elseif (($group['type'] ?? '') === 'repeater')
                                {{-- Dynamic social links: icon + title + url, add/remove rows. --}}
                                <div class="form-field" x-data="{
                                    rows: @js($socialRows),
                                    icons: @js($iconChoices),
                                    openPicker: null,
                                    query: '',
                                    add() { this.rows.push({ icon: '', title: '', url: '' }); },
                                    remove(i) { this.rows.splice(i, 1); if (this.rows.length === 0) this.add(); },
                                    open(i) { this.openPicker = (this.openPicker === i ? null : i); this.query = ''; },
                                    filtered() {
                                        const q = this.query.trim().toLowerCase();
                                        if (!q) return this.icons;
                                        return this.icons.filter(ic => ic.k.includes(q) || ic.c.includes(q));
                                    }
                                }">
                                    <div class="dynamic-field-header">
                                        <label>Social links</label>
                                        <button type="button" class="dynamic-add-button" @click="add()">
                                            <i class="fa-solid fa-plus"></i> Add link
                                        </button>
                                    </div>

                                    <div class="social-rows">
                                        <template x-for="(row, i) in rows" :key="i">
                                            <div class="social-row">
                                                <div class="icon-picker">
                                                    <input type="hidden" :name="`social_links[${i}][icon]`" :value="row.icon">

                                                    <button type="button" class="icon-picker__trigger" @click="open(i)">
                                                        <i :class="row.icon || 'fa-solid fa-icons'"></i>
                                                        <i class="fa-solid fa-chevron-down icon-picker__caret"></i>
                                                    </button>

                                                    <div class="icon-picker__panel" x-show="openPicker === i" x-cloak
                                                        @click.outside="openPicker = null">
                                                        <div class="icon-picker__search">
                                                            <i class="fa-solid fa-magnifying-glass"></i>
                                                            <input type="text" x-model="query" placeholder="Search icons…">
                                                        </div>

                                                        <div class="icon-picker__grid">
                                                            <template x-for="ic in (openPicker === i ? filtered() : [])" :key="ic.c">
                                                                <button type="button" class="icon-picker__option" :title="ic.k"
                                                                    :class="{ 'is-active': row.icon === ic.c }"
                                                                    @click="row.icon = ic.c; openPicker = null">
                                                                    <i :class="ic.c"></i>
                                                                </button>
                                                            </template>
                                                            <p class="icon-picker__empty" x-show="filtered().length === 0">No icons found</p>
                                                        </div>

                                                        <div class="icon-picker__custom">
                                                            <span>Or paste any FontAwesome class</span>
                                                            <div class="icon-picker__custom-row">
                                                                <i :class="row.icon || 'fa-regular fa-square'"></i>
                                                                <input type="text" x-model="row.icon" placeholder="fa-brands fa-figma">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <input type="text" class="form-input" :name="`social_links[${i}][title]`"
                                                    x-model="row.title" placeholder="Title (e.g. Facebook)">

                                                <input type="url" class="form-input" :name="`social_links[${i}][url]`"
                                                    x-model="row.url" placeholder="https://...">

                                                <button type="button" class="dynamic-remove-button" @click="remove(i)" title="Remove">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    @error('social_links')
                                        <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                                    @enderror
                                    @foreach ($errors->keys() as $errorKey)
                                        @if (str_starts_with($errorKey, 'social_links.'))
                                            <p class="text-red-500 text-sm mt-1.5">{{ $errors->first($errorKey) }}</p>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @endif
                    @endforeach

                    <div class="form-panel-footer">
                        <button type="submit" class="form-submit-button">
                            <i class="fa-solid fa-check"></i>
                            {{ __('Save changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>

</x-app-layout>