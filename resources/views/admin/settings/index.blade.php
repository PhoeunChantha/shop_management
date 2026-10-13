<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Configuration') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Settings') }}
            </h2>
        </div>
    </x-slot>

    @php
        // Open the tab that contains the first validation error, otherwise the
        // ?tab= deep link (e.g. from the chat inbox), otherwise General.
        $activeTab = array_key_exists((string) request('tab'), $schema) ? (string) request('tab') : array_key_first($schema);
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
        if ($errors->has('footer_links') || collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'footer_links'))) {
            $activeTab = 'footer';
        }
    @endphp

    <div class="" x-data="{ tab: '{{ $activeTab }}' }">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Site setup') }}</p>
                <h3>{{ __('Manage settings') }}</h3>
            </div>
        </div>

        <section class="premium-card form-panel settings-layout">
            <aside class="settings-tabs">
                @foreach ($schema as $groupKey => $group)
                    <button type="button" class="settings-tab" data-tab="{{ $groupKey }}" :class="{ 'is-active': tab === '{{ $groupKey }}' }"
                        @click="tab = '{{ $groupKey }}'">
                        <i class="fa-solid {{ $group['icon'] }}"></i>
                        <span>{{ $group['label'] }}</span>
                    </button>
                @endforeach
            </aside>

            <div class="settings-content">
                <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data"
                    id="settings-form" data-ajax-form>
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
                                data-tab-panel="{{ $groupKey }}"
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
                                        <i class="fa-solid fa-rotate-left"></i> {{ __('Reset to defaults') }}
                                    </button>
                                    <span class="color-reset-hint">{{ __('Resets the colors above — click “Save changes” to apply.') }}</span>
                                </div>
                            </div>
                        @else
                        <div class="form-panel-body {{ ($group['type'] ?? 'fields') === 'fields' ? 'grid-cols-1 sm:grid-cols-2 gap-x-5' : '' }}"
                            data-tab-panel="{{ $groupKey }}"
                            x-show="tab === '{{ $groupKey }}'" x-cloak>
                            @if ($groupKey === 'facebook')
                                <div class="sm:col-span-2" style="margin-bottom:16px;padding:12px 14px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb">
                                    <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px">{{ __('Product catalog feed URL') }}</label>
                                    <input type="text" readonly value="{{ route('frontend.feed.products') }}" onclick="this.select()"
                                        style="width:100%;font-size:13px;padding:7px 9px;border:1px solid #d1d5db;border-radius:6px;background:#fff">
                                    <p style="font-size:12px;color:#6b7280;margin:6px 0 0">
                                        {{ __('Paste this into Meta Commerce Manager (or Google Merchant Center) as a scheduled feed to keep your whole catalog — price, stock, images — in sync automatically. No app or access token needed for this.') }}
                                    </p>
                                </div>
                            @endif
                            @if ($groupKey === 'cutluy')
                                <div class="sm:col-span-2" style="margin-bottom:16px;padding:12px 14px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb"
                                    x-data="{
                                        busy: false, result: null,
                                        async test() {
                                            this.busy = true; this.result = null;
                                            try {
                                                const res = await fetch(@js(route('admin.settings.cutluy-test')), {
                                                    method: 'POST',
                                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
                                                });
                                                this.result = await res.json();
                                            } catch (e) {
                                                this.result = { ok: false, message: @js(__('Request failed — try again.')) };
                                            }
                                            this.busy = false;
                                        }
                                    }">
                                    <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;color:#111827">{{ __('Webhook URL') }}</label>
                                    <input type="text" readonly onclick="this.select()" value="{{ rtrim((string) config('app.url'), '/').route('frontend.webhooks.cutluy', [], false) }}"
                                        style="width:100%;font-size:13px;padding:7px 9px;border:1px solid #d1d5db;border-radius:6px;background:#fff">
                                    <p style="font-size:12px;color:#6b7280;margin:6px 0 10px">
                                        {{ __('Add this URL as a webhook endpoint in your CutLuy dashboard (events: payment.completed, payment.scanned, payment.expired, payment.failed), then paste the signing secret below. It must be a public https address — a local 127.0.0.1 address cannot receive webhooks.') }}
                                    </p>
                                    <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;color:#111827">{{ __('Checkout redirect URL') }}</label>
                                    <input type="text" readonly onclick="this.select()" value="{{ rtrim((string) config('app.url'), '/').route('frontend.payment.cutluy.return', [], false) }}"
                                        style="width:100%;font-size:13px;padding:7px 9px;border:1px solid #d1d5db;border-radius:6px;background:#fff">
                                    <p style="font-size:12px;color:#6b7280;margin:6px 0 10px">
                                        {{ __('In your CutLuy store settings, paste this same URL into both “Success redirect URL” and “Failure redirect URL”. It brings customers who paid on the CutLuy page back to their order or wallet.') }}
                                    </p>
                                    <p style="font-size:12px;color:#6b7280;margin:0 0 8px">
                                        {{ __('To use it: open Payment Methods, set a method to Type “Online” and Gateway “CutLuy (KHQR)”. Save your keys first, then check the connection.') }}
                                    </p>
                                    <button type="button" class="form-cancel-button" @click="test()" :disabled="busy">
                                        <i class="fa-solid" :class="busy ? 'fa-spinner fa-spin' : 'fa-plug'"></i> {{ __('Check connection & quota') }}
                                    </button>
                                    <p x-show="result" x-cloak style="font-size:13px;margin:8px 0 0"
                                        :style="{ color: result?.ok ? '#16a34a' : '#dc2626' }" x-text="result?.message"></p>
                                </div>
                            @endif
                            @if ($groupKey === 'storage')
                                <div class="sm:col-span-2" style="margin-bottom:16px;padding:12px 14px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb"
                                    x-data="{
                                        busy: false, result: null,
                                        async test() {
                                            this.busy = true; this.result = null;
                                            try {
                                                const res = await fetch(@js(route('admin.settings.storage-test')), {
                                                    method: 'POST',
                                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
                                                });
                                                this.result = await res.json();
                                            } catch (e) {
                                                this.result = { ok: false, message: @js(__('Request failed — try again.')) };
                                            }
                                            this.busy = false;
                                        }
                                    }">
                                    <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px;color:#111827">{{ __('Cloudflare R2 connection') }}</label>
                                    <p style="font-size:12px;color:#6b7280;margin:0 0 8px">
                                        {{ __('Save your changes first, then test. The test uploads a tiny file, loads it from the public URL and deletes it.') }}
                                    </p>
                                    <button type="button" class="form-cancel-button" @click="test()" :disabled="busy">
                                        <i class="fa-solid" :class="busy ? 'fa-spinner fa-spin' : 'fa-plug'"></i> {{ __('Test connection') }}
                                    </button>
                                    <p x-show="result" x-cloak style="font-size:13px;margin:8px 0 0"
                                        :style="{ color: result?.ok ? '#16a34a' : '#dc2626' }" x-text="result?.message"></p>
                                </div>
                            @endif
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
                                                            <button type="button" @click="remove(code)" aria-label="{{ __('Remove') }}">{{ __('&times;') }}</button>
                                                        </span>
                                                    </template>
                                                    <span class="ms-select__placeholder" x-show="selected.length === 0">{{ __('Select languages…') }}</span>
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
                                        @elseif (($field['type'] ?? 'text') === 'password')
                                            {{-- Never echo the stored secret into page source; blank = keep current. --}}
                                            <input type="password" name="{{ $fieldKey }}" id="{{ $fieldKey }}"
                                                value="" autocomplete="new-password"
                                                class="form-input" placeholder="{{ filled($values[$fieldKey] ?? '') ? '•••••••• saved — leave blank to keep' : ($field['placeholder'] ?? '') }}">
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
                                @if ($groupKey === 'currencies')
                                    {{-- Extra display currencies. Browsing only: orders are charged in the base
                                         currency above. Row order = order in the storefront switcher. --}}
                                    <div class="sm:col-span-2 form-field" style="margin-top:8px"
                                        x-data="{
                                            rows: @js($currencyRows),
                                            base: @js(['symbol' => $values['currency_symbol'] ?? '$', 'position' => $values['currency_position'] ?? 'before']),
                                            add() { this.rows.push({ code: '', name: '', symbol: '', symbol_position: 'after', exchange_rate: '', decimals: '0', status: '1' }); },
                                            remove(i) { this.rows.splice(i, 1); },
                                            move(i, d) { const j = i + d; if (j < 0 || j >= this.rows.length) return; const r = this.rows.splice(i, 1)[0]; this.rows.splice(j, 0, r); },
                                            sample(r) {
                                                const rate = parseFloat(r.exchange_rate);
                                                if (!(rate > 0) || !r.symbol) return '—';
                                                const d = parseInt(r.decimals, 10) || 0;
                                                const n = (10 * rate).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
                                                return r.symbol_position === 'after' ? n + r.symbol : r.symbol + n;
                                            },
                                            baseTen() { return this.base.position === 'after' ? '10.00' + this.base.symbol : this.base.symbol + '10.00'; }
                                        }">
                                        <input type="hidden" name="currencies_submitted" value="1">

                                        <div class="dynamic-field-header">
                                            <div>
                                                <label>{{ __('Extra display currencies') }}</label>
                                                <small class="text-gray-400 dark:text-slate-500 d-block mt-1">{{ __('Shoppers can switch the prices they browse to any enabled currency. Checkout, payments, wallet and invoices still use the base currency. Rate = how many of this currency equal 1 base unit.') }}</small>
                                            </div>
                                            <button type="button" class="dynamic-add-button" style="white-space:nowrap;flex-shrink:0" @click="add()">
                                                <i class="fa-solid fa-plus"></i> {{ __('Add currency') }}
                                            </button>
                                        </div>

                                        <p x-show="rows.length === 0" x-cloak class="text-sm text-gray-500 dark:text-slate-400" style="margin:6px 0 0">
                                            {{ __('No extra currencies yet. Add one, e.g. KHR.') }}
                                        </p>

                                        <div class="currency-rows">
                                            <template x-for="(row, i) in rows" :key="i">
                                                <div class="currency-row">
                                                    <div class="currency-row__cell currency-row__cell--code">
                                                        <span class="currency-row__label">{{ __('Code') }}</span>
                                                        <input type="text" class="form-input" maxlength="3" style="text-transform:uppercase"
                                                            :name="`currencies[${i}][code]`" x-model="row.code" placeholder="KHR">
                                                    </div>
                                                    <div class="currency-row__cell currency-row__cell--name">
                                                        <span class="currency-row__label">{{ __('Name') }}</span>
                                                        <input type="text" class="form-input" :name="`currencies[${i}][name]`" x-model="row.name" placeholder="{{ __('e.g. Cambodian Riel') }}">
                                                    </div>
                                                    <div class="currency-row__cell currency-row__cell--sym">
                                                        <span class="currency-row__label">{{ __('Symbol') }}</span>
                                                        <input type="text" class="form-input" maxlength="8" :name="`currencies[${i}][symbol]`" x-model="row.symbol" placeholder="៛">
                                                    </div>
                                                    <div class="currency-row__cell">
                                                        <span class="currency-row__label">{{ __('Position') }}</span>
                                                        <select class="form-input" :name="`currencies[${i}][symbol_position]`" x-model="row.symbol_position">
                                                            <option value="before">{{ __('Before') }} (€10)</option>
                                                            <option value="after">{{ __('After') }} (10៛)</option>
                                                        </select>
                                                    </div>
                                                    <div class="currency-row__cell">
                                                        <span class="currency-row__label">{{ __('Rate') }}</span>
                                                        <input type="number" step="any" min="0" class="form-input" :name="`currencies[${i}][exchange_rate]`" x-model="row.exchange_rate" placeholder="4100">
                                                    </div>
                                                    <div class="currency-row__cell">
                                                        <span class="currency-row__label">{{ __('Decimals') }}</span>
                                                        <select class="form-input" :name="`currencies[${i}][decimals]`" x-model="row.decimals">
                                                            <option value="0">0</option>
                                                            <option value="2">2</option>
                                                            <option value="3">3</option>
                                                        </select>
                                                    </div>
                                                    <div class="currency-row__cell">
                                                        <span class="currency-row__label">{{ __('Status') }}</span>
                                                        <select class="form-input" :name="`currencies[${i}][status]`" x-model="row.status">
                                                            <option value="1">{{ __('Enabled') }}</option>
                                                            <option value="0">{{ __('Disabled') }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="currency-row__tools">
                                                        <span class="currency-row__sample" x-text="baseTen() + ' → ' + sample(row)"></span>
                                                        <button type="button" class="dynamic-remove-button" style="background:none;color:inherit" @click="move(i, -1)" :disabled="i === 0" title="{{ __('Move up') }}"><i class="fa-solid fa-arrow-up"></i></button>
                                                        <button type="button" class="dynamic-remove-button" style="background:none;color:inherit" @click="move(i, 1)" :disabled="i === rows.length - 1" title="{{ __('Move down') }}"><i class="fa-solid fa-arrow-down"></i></button>
                                                        <button type="button" class="dynamic-remove-button" @click="remove(i)" title="{{ __('Remove') }}"><i class="fa-solid fa-trash"></i></button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        @foreach ($errors->keys() as $errorKey)
                                            @if (str_starts_with($errorKey, 'currencies'))
                                                <p class="text-red-500 text-sm mt-1.5">{{ $errors->first($errorKey) }}</p>
                                            @endif
                                        @endforeach
                                    </div>

                                    <style>
                                        .currency-rows { display: flex; flex-direction: column; gap: 12px; margin-top: 12px; }
                                        .currency-row { display: grid; grid-template-columns: 90px minmax(140px, 1.4fr) 80px 130px 110px 90px 120px; gap: 10px; padding: 12px; border: 1px solid rgba(148, 163, 184, .35); border-radius: 12px; align-items: end; }
                                        .currency-row__cell { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
                                        .currency-row__label { font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; opacity: .6; }
                                        .currency-row__tools { grid-column: 1 / -1; display: flex; align-items: center; gap: 6px; }
                                        .currency-row__sample { margin-right: auto; font-size: 13px; opacity: .75; font-variant-numeric: tabular-nums; }
                                        .currency-row__tools button { width: 34px; height: 34px; flex: 0 0 34px; }
                                        .currency-row__tools button:disabled { opacity: .3; cursor: default; }
                                        @media (max-width: 1100px) { .currency-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } .currency-row__cell--name { grid-column: 1 / -1; } }
                                    </style>
                                @endif
                            @elseif (($group['type'] ?? '') === 'payment_methods')
                                <div class="payment-method-builder sm:col-span-2"
                                    x-data="paymentMethodSettings(@js($paymentRows))">
                                    <div class="payment-method-builder__head">
                                        <div>
                                            <p class="section-kicker">{{ __('Checkout options') }}</p>
                                            <h3>{{ __('Payment methods') }}</h3>
                                            <span>{{ __('Manage the methods shown by checkout later. Saved as one settings array.') }}</span>
                                        </div>
                                        <button type="button" class="dynamic-add-button" @click="add()">
                                            <i class="fa-solid fa-plus"></i> {{ __('Add method') }}
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
                                                        <button type="button" class="dynamic-remove-button" @click="remove(i)" title="{{ __('Delete method') }}">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </div>

                                                    <div class="payment-method-grid">
                                                        <div class="form-field">
                                                            <label>{{ __('Name') }} <span class="text-red-500">*</span></label>
                                                            <input type="text" class="form-input" :name="`payment_methods[${i}][name]`"
                                                                x-model="method.name" placeholder="{{ __('Card') }}"
                                                                data-required data-required-label="{{ __('Payment method name') }}">
                                                        </div>
                                                        <div class="form-field">
                                                            <label>{{ __('Code') }}</label>
                                                            <input type="text" class="form-input" :name="`payment_methods[${i}][code]`"
                                                                x-model="method.code" placeholder="{{ __('card') }}">
                                                        </div>
                                                        <div class="form-field">
                                                            <label>{{ __('Sort') }}</label>
                                                            <input type="number" class="form-input" :name="`payment_methods[${i}][sort_order]`"
                                                                x-model="method.sort_order" min="0" max="999">
                                                        </div>
                                                        <div class="form-field">
                                                            <label>{{ __('Type') }}</label>
                                                            <select class="form-input" :name="`payment_methods[${i}][type]`" x-model="method.type">
                                                                <option value="online">{{ __('Online') }}</option>
                                                                <option value="manual">{{ __('Manual / QR') }}</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-field" x-show="method.type === 'online'">
                                                            <label>{{ __('Gateway') }}</label>
                                                            <select class="form-input" :name="`payment_methods[${i}][gateway]`" x-model="method.gateway">
                                                                <option value="payway">{{ __('ABA PayWay (card)') }}</option>
                                                                <option value="cutluy">{{ __('CutLuy (KHQR)') }}</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-field payment-method-grid__wide">
                                                            <label>{{ __('Description') }}</label>
                                                            <input type="text" class="form-input" :name="`payment_methods[${i}][description]`"
                                                                x-model="method.description" placeholder="{{ __('Short admin or checkout description') }}">
                                                        </div>
                                                        <div class="form-field" x-show="method.type === 'manual'" x-cloak>
                                                            <label>{{ __('Bank / wallet') }}</label>
                                                            <input type="text" class="form-input" :name="`payment_methods[${i}][bank_name]`"
                                                                x-model="method.bank_name" placeholder="{{ __('ABA, Wing, ACLEDA') }}">
                                                        </div>
                                                        <div class="form-field" x-show="method.type === 'manual'" x-cloak>
                                                            <label>{{ __('Account name') }}</label>
                                                            <input type="text" class="form-input" :name="`payment_methods[${i}][account_name]`"
                                                                x-model="method.account_name" placeholder="{{ __('Store account name') }}">
                                                        </div>
                                                        <div class="form-field" x-show="method.type === 'manual'" x-cloak>
                                                            <label>{{ __('Account number') }}</label>
                                                            <input type="text" class="form-input" :name="`payment_methods[${i}][account_number]`"
                                                                x-model="method.account_number" placeholder="{{ __('Account or phone number') }}">
                                                        </div>
                                                        <div class="form-field payment-method-grid__wide">
                                                            <label>{{ __('Related field / instructions') }}</label>
                                                            <textarea rows="2" class="form-input" :name="`payment_methods[${i}][instructions]`"
                                                                x-model="method.instructions" placeholder="{{ __('Example: require card number, expiry and CVC') }}"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </article>
                                        </template>
                                    </div>

                                    <button type="button" class="payment-method-empty-add" x-show="methods.length === 0" x-cloak @click="add()">
                                        <i class="fa-solid fa-plus"></i>
                                        <span>{{ __('Add your first payment method') }}</span>
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
                                        <label>{{ __('Social links') }}</label>
                                        <button type="button" class="dynamic-add-button" @click="add()">
                                            <i class="fa-solid fa-plus"></i> {{ __('Add link') }}
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
                                                            <input type="text" x-model="query" placeholder="{{ __('Search icons…') }}">
                                                        </div>

                                                        <div class="icon-picker__grid">
                                                            <template x-for="ic in (openPicker === i ? filtered() : [])" :key="ic.c">
                                                                <button type="button" class="icon-picker__option" :title="ic.k"
                                                                    :class="{ 'is-active': row.icon === ic.c }"
                                                                    @click="row.icon = ic.c; openPicker = null">
                                                                    <i :class="ic.c"></i>
                                                                </button>
                                                            </template>
                                                            <p class="icon-picker__empty" x-show="filtered().length === 0">{{ __('No icons found') }}</p>
                                                        </div>

                                                        <div class="icon-picker__custom">
                                                            <span>{{ __('Or paste any FontAwesome class') }}</span>
                                                            <div class="icon-picker__custom-row">
                                                                <i :class="row.icon || 'fa-regular fa-square'"></i>
                                                                <input type="text" x-model="row.icon" placeholder="{{ __('fa-brands fa-figma') }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <input type="text" class="form-input" :name="`social_links[${i}][title]`"
                                                    x-model="row.title" placeholder="{{ __('Title (e.g. Facebook)') }}">

                                                <input type="url" class="form-input" :name="`social_links[${i}][url]`"
                                                    x-model="row.url" placeholder="{{ __('https://...') }}">

                                                <button type="button" class="dynamic-remove-button" @click="remove(i)" title="{{ __('Remove') }}">
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
                            @elseif (($group['type'] ?? '') === 'footer_menu')
                                {{-- Footer menu: group links by a column heading (label + url). --}}
                                <div class="form-field" x-data="{
                                    rows: @js($footerRows),
                                    add() { this.rows.push({ column: '', label: '', url: '' }); },
                                    remove(i) { this.rows.splice(i, 1); if (this.rows.length === 0) this.add(); }
                                }">
                                    <div class="dynamic-field-header">
                                        <div>
                                            <label>{{ __('Footer menu links') }}</label>
                                            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">{{ __('Group links by a column heading (e.g. Help, Brand). The “Shop” column is generated from your categories automatically.') }}</small>
                                        </div>
                                        <button type="button" class="dynamic-add-button" @click="add()">
                                            <i class="fa-solid fa-plus"></i> {{ __('Add link') }}
                                        </button>
                                    </div>

                                    <div class="social-rows">
                                        <template x-for="(row, i) in rows" :key="i">
                                            <div class="social-row">
                                                <input type="text" class="form-input" :name="`footer_links[${i}][column]`"
                                                    x-model="row.column" placeholder="{{ __('Column (e.g. Help)') }}">
                                                <input type="text" class="form-input" :name="`footer_links[${i}][label]`"
                                                    x-model="row.label" placeholder="{{ __('Label (e.g. Shipping)') }}">
                                                <input type="text" class="form-input" :name="`footer_links[${i}][url]`"
                                                    x-model="row.url" placeholder="{{ __('https://… or /shop') }}">
                                                <button type="button" class="dynamic-remove-button" @click="remove(i)" title="{{ __('Remove') }}">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    @foreach ($errors->keys() as $errorKey)
                                        @if (str_starts_with($errorKey, 'footer_links'))
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
                        gateway: 'payway',
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
