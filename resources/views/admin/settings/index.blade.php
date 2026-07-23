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
        if ($errors->has('payment_methods') || $errors->has('payment_method_images') || collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'payment_methods') || str_starts_with($k, 'payment_method_images'))) {
            $activeTab = 'payment';
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
                                        @elseif (($field['type'] ?? 'text') === 'select')
                                            @php($selectedVal = (string) old($fieldKey, $values[$fieldKey] ?? ($field['default'] ?? '')))
                                            <select name="{{ $fieldKey }}" id="{{ $fieldKey }}" class="form-input">
                                                @foreach ($field['options'] ?? [] as $optVal => $optLabel)
                                                    <option value="{{ $optVal }}" @selected($selectedVal === (string) $optVal)>{{ $optLabel }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="{{ $field['type'] ?? 'text' }}" name="{{ $fieldKey }}" id="{{ $fieldKey }}"
                                                value="{{ old($fieldKey, $values[$fieldKey] ?? '') }}"
                                                class="form-input" placeholder="{{ $field['placeholder'] ?? '' }}">
                                        @endif

                                        @if (!empty($field['help']))
                                            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">{{ $field['help'] }}</small>
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
                                                :folder="$field['folder'] ?? 'settings'"
                                                :value="\App\Helpers\ImageManager::path($values[$fieldKey] ?? null, $field['folder'] ?? 'settings')"
                                                :accept="$field['accept'] ?? 'image/*'"
                                                :help="$field['help'] ?? 'PNG, JPG, GIF or SVG — up to 2MB'" />
                                        @endforeach
                                    </div>
                                @endif
                            @elseif (($group['type'] ?? '') === 'payment_methods')
                                <div class="payment-method-builder sm:col-span-2"
                                    x-data="paymentMethodSettings(@js($paymentRows))">
                                    <div class="payment-method-builder__head">
                                        <div>
                                            <p class="section-kicker">Checkout options</p>
                                            <h3>Payment methods</h3>
                                            <span>Manage the methods shown by checkout later. Saved as one settings array.</span>
                                        </div>
                                        <button type="button" class="dynamic-add-button" @click="add()">
                                            <i class="fa-solid fa-plus"></i> Add method
                                        </button>
                                    </div>

                                    <div class="payment-method-list">
                                        <template x-for="(method, i) in methods" :key="method.id || i">
                                            <article class="payment-method-card">
                                                <input type="hidden" :name="`payment_methods[${i}][id]`" x-model="method.id">
                                                <input type="hidden" :name="`payment_methods[${i}][image]`" x-model="method.image">
                                                <input type="hidden" :name="`payment_methods[${i}][qr_image]`" x-model="method.qr_image">

                                                <div class="payment-method-card__media">
                                                    <div class="payment-method-thumb">
                                                        <template x-if="previewUrl(method)">
                                                            <img :src="previewUrl(method)" alt="">
                                                        </template>
                                                        <template x-if="!previewUrl(method)">
                                                            <span><i class="fa-regular fa-credit-card"></i></span>
                                                        </template>
                                                    </div>
                                                    <label class="payment-method-upload">
                                                        <i class="fa-solid fa-image"></i>
                                                        <span x-text="method.image ? 'Replace image' : 'Upload image'"></span>
                                                        <input type="file" class="visually-hidden" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                                            :name="`payment_method_images[${i}]`" @change="pickImage($event, method)">
                                                    </label>
                                                    <div class="payment-method-qr" x-show="method.type === 'manual'" x-cloak>
                                                        <div class="payment-method-qr__preview">
                                                            <template x-if="qrPreviewUrl(method)">
                                                                <img :src="qrPreviewUrl(method)" alt="">
                                                            </template>
                                                            <template x-if="!qrPreviewUrl(method)">
                                                                <span><i class="fa-solid fa-qrcode"></i></span>
                                                            </template>
                                                        </div>
                                                        <label class="payment-method-upload payment-method-upload--qr">
                                                            <i class="fa-solid fa-qrcode"></i>
                                                            <span x-text="method.qr_image ? 'Replace QR' : 'Upload QR'"></span>
                                                            <input type="file" class="visually-hidden" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                                                :name="`payment_method_qr_images[${i}]`" @change="pickQrImage($event, method)">
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="payment-method-card__fields">
                                                    <div class="payment-method-card__top">
                                                        <label class="payment-toggle">
                                                            <input type="hidden" :name="`payment_methods[${i}][status]`" value="0">
                                                            <input type="checkbox" :name="`payment_methods[${i}][status]`" value="1" x-model="method.status">
                                                            <span></span>
                                                            <strong x-text="method.status ? 'Enabled' : 'Disabled'"></strong>
                                                        </label>
                                                        <button type="button" class="dynamic-remove-button" @click="remove(i)" title="Delete method">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </div>

                                                    <div class="payment-method-grid">
                                                        <div class="form-field">
                                                            <label>Name</label>
                                                            <input type="text" class="form-input" :name="`payment_methods[${i}][name]`"
                                                                x-model="method.name" placeholder="Card">
                                                        </div>
                                                        <div class="form-field">
                                                            <label>Code</label>
                                                            <input type="text" class="form-input" :name="`payment_methods[${i}][code]`"
                                                                x-model="method.code" placeholder="card">
                                                        </div>
                                                        <div class="form-field">
                                                            <label>Sort</label>
                                                            <input type="number" class="form-input" :name="`payment_methods[${i}][sort_order]`"
                                                                x-model="method.sort_order" min="0" max="999">
                                                        </div>
                                                        <div class="form-field">
                                                            <label>Type</label>
                                                            <select class="form-input" :name="`payment_methods[${i}][type]`" x-model="method.type">
                                                                <option value="online">Online</option>
                                                                <option value="manual">Manual / QR</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-field payment-method-grid__wide">
                                                            <label>Description</label>
                                                            <input type="text" class="form-input" :name="`payment_methods[${i}][description]`"
                                                                x-model="method.description" placeholder="Short admin or checkout description">
                                                        </div>
                                                        <div class="form-field" x-show="method.type === 'manual'" x-cloak>
                                                            <label>Bank / wallet</label>
                                                            <input type="text" class="form-input" :name="`payment_methods[${i}][bank_name]`"
                                                                x-model="method.bank_name" placeholder="ABA, Wing, ACLEDA">
                                                        </div>
                                                        <div class="form-field" x-show="method.type === 'manual'" x-cloak>
                                                            <label>Account name</label>
                                                            <input type="text" class="form-input" :name="`payment_methods[${i}][account_name]`"
                                                                x-model="method.account_name" placeholder="Store account name">
                                                        </div>
                                                        <div class="form-field" x-show="method.type === 'manual'" x-cloak>
                                                            <label>Account number</label>
                                                            <input type="text" class="form-input" :name="`payment_methods[${i}][account_number]`"
                                                                x-model="method.account_number" placeholder="Account or phone number">
                                                        </div>
                                                        <div class="form-field payment-method-grid__wide">
                                                            <label>Related field / instructions</label>
                                                            <textarea rows="2" class="form-input" :name="`payment_methods[${i}][instructions]`"
                                                                x-model="method.instructions" placeholder="Example: require card number, expiry and CVC"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </article>
                                        </template>
                                    </div>

                                    <button type="button" class="payment-method-empty-add" x-show="methods.length === 0" x-cloak @click="add()">
                                        <i class="fa-solid fa-plus"></i>
                                        <span>Add your first payment method</span>
                                    </button>

                                    @foreach ($errors->keys() as $errorKey)
                                        @if (str_starts_with($errorKey, 'payment_methods') || str_starts_with($errorKey, 'payment_method_images'))
                                            <p class="text-red-500 text-sm mt-1.5">{{ $errors->first($errorKey) }}</p>
                                        @endif
                                    @endforeach
                                </div>
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

    <script>
        function paymentMethodSettings(initialMethods) {
            return {
                methods: Array.isArray(initialMethods) ? initialMethods.map((method, index) => ({
                    id: method.id || `payment_${index + 1}`,
                    name: method.name || '',
                    code: method.code || '',
                    type: method.type || 'online',
                    description: method.description || '',
                    instructions: method.instructions || '',
                    image: method.image || '',
                    qr_image: method.qr_image || '',
                    bank_name: method.bank_name || '',
                    account_name: method.account_name || '',
                    account_number: method.account_number || '',
                    preview: '',
                    qr_preview: '',
                    status: Boolean(method.status),
                    sort_order: method.sort_order || index + 1,
                })) : [],
                publicRoot: @js(rtrim(asset(''), '/')),
                settingsRoot: @js(rtrim(asset('uploads/settings'), '/')),
                add() {
                    const index = this.methods.length + 1;
                    this.methods.push({
                        id: `payment_${Date.now()}`,
                        name: '',
                        code: '',
                        description: '',
                        instructions: '',
                        image: '',
                        qr_image: '',
                        preview: '',
                        qr_preview: '',
                        type: 'online',
                        bank_name: '',
                        account_name: '',
                        account_number: '',
                        status: true,
                        sort_order: index,
                    });
                },
                remove(index) {
                    this.methods.splice(index, 1);
                },
                pickImage(event, method) {
                    const file = event.target.files?.[0];
                    if (!file) return;
                    method.preview = URL.createObjectURL(file);
                },
                pickQrImage(event, method) {
                    const file = event.target.files?.[0];
                    if (!file) return;
                    method.qr_preview = URL.createObjectURL(file);
                },
                previewUrl(method) {
                    if (method.preview) return method.preview;
                    if (!method.image) return '';
                    if (method.image.startsWith('http://') || method.image.startsWith('https://')) return method.image;
                    if (method.image.startsWith('uploads/')) return `${this.publicRoot}/${method.image}`;
                    return `${this.settingsRoot}/${method.image}`;
                },
                qrPreviewUrl(method) {
                    if (method.qr_preview) return method.qr_preview;
                    if (!method.qr_image) return '';
                    if (method.qr_image.startsWith('http://') || method.qr_image.startsWith('https://')) return method.qr_image;
                    if (method.qr_image.startsWith('uploads/')) return `${this.publicRoot}/${method.qr_image}`;
                    return `${this.settingsRoot}/${method.qr_image}`;
                },
            };
        }
    </script>

</x-app-layout>
