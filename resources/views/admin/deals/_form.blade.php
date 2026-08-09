@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $d = $deal ?? null;
    $selectedIds = array_map('intval', $selected ?? []);
    $startsAt = old('starts_at', $d?->starts_at?->format('Y-m-d\TH:i'));
    $endsAt = old('ends_at', $d?->ends_at?->format('Y-m-d\TH:i'));
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="deal-form-shell">
        <section class="deal-form-section deal-form-section--main">
            <div class="deal-form-section__head">
                <span><i class="fa-solid fa-bullseye"></i></span>
                <div>
                    <p>{{ __('Campaign') }}</p>
                    <h4>{{ __('Core offer details') }}</h4>
                </div>
            </div>

            <div class="deal-form-grid">
                <div class="form-field deal-span-2">
                    <label for="title">Deal title <span class="text-red-500">*</span></label>
                    <input value="{{ old('title', $d->title ?? '') }}" type="text" name="title" id="title"
                        class="form-input" placeholder="{{ __('Weekend Flash Sale') }}" required>
                    @error('title')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label for="type">Deal type <span class="text-red-500">*</span></label>
                    <select name="type" id="type" class="form-input" required>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $d->type ?? 'flash') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="form-field deal-span-2">
                    <label for="summary">{{ __('Summary') }}</label>
                    <textarea name="summary" id="summary" class="form-input deal-form-textarea" rows="3"
                        placeholder="{{ __('Short campaign copy for admins and future storefront placement') }}">{{ old('summary', $d->summary ?? '') }}</textarea>
                    @error('summary')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label for="badge">{{ __('Badge label') }}</label>
                    <input value="{{ old('badge', $d->badge ?? '') }}" type="text" name="badge" id="badge"
                        class="form-input" placeholder="{{ __('Up to 40% off') }}">
                    @error('badge')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <aside class="deal-form-section deal-form-section--media">
            <div class="deal-form-section__head">
                <span><i class="fa-solid fa-image"></i></span>
                <div>
                    <p>{{ __('Artwork') }}</p>
                    <h4>{{ __('Campaign image') }}</h4>
                </div>
            </div>
            <x-image-upload name="image" label="{{ __('Campaign image') }}" folder="deals"
                :value="$d && $d->image ? 'uploads/deals/' . $d->image : null"
                help="Optional - JPG, PNG or WebP, 5:1 artwork recommended" />
        </aside>

        <section class="deal-form-section deal-form-section--compact">
            <div class="deal-form-section__head">
                <span><i class="fa-solid fa-percent"></i></span>
                <div>
                    <p>{{ __('Pricing') }}</p>
                    <h4>{{ __('Discount setup') }}</h4>
                </div>
            </div>

            <div class="deal-form-grid deal-form-grid--three">
                <div class="form-field">
                    <label for="discount_type">{{ __('Discount type') }}</label>
                    <select name="discount_type" id="discount_type" class="form-input">
                        <option value="" @selected(old('discount_type', $d->discount_type ?? '') === '')>{{ __('Campaign only') }}</option>
                        <option value="percentage" @selected(old('discount_type', $d->discount_type ?? '') === 'percentage')>{{ __('Percentage') }}</option>
                        <option value="fixed" @selected(old('discount_type', $d->discount_type ?? '') === 'fixed')>{{ __('Fixed amount') }}</option>
                    </select>
                    @error('discount_type')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label for="discount_value">{{ __('Discount value') }}</label>
                    <input value="{{ old('discount_value', $d->discount_value ?? 0) }}" type="number" step="0.01" min="0"
                        name="discount_value" id="discount_value" class="form-input" placeholder="0.00">
                    @error('discount_value')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label for="priority">{{ __('Priority') }}</label>
                    <input value="{{ old('priority', $d->priority ?? 0) }}" type="number" min="0" max="65535"
                        name="priority" id="priority" class="form-input" placeholder="0">
                    <small class="form-help">{{ __('Higher priority appears first.') }}</small>
                    @error('priority')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="deal-form-section deal-form-section--compact">
            <div class="deal-form-section__head">
                <span><i class="fa-solid fa-calendar-days"></i></span>
                <div>
                    <p>{{ __('Schedule') }}</p>
                    <h4>{{ __('Timing and publishing') }}</h4>
                </div>
            </div>

            <div class="deal-form-grid deal-form-grid--three">
                <div class="form-field">
                    <label for="starts_at">{{ __('Start date') }}</label>
                    <input value="{{ $startsAt }}" type="datetime-local" name="starts_at" id="starts_at" class="form-input">
                    @error('starts_at')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label for="ends_at">{{ __('End date') }}</label>
                    <input value="{{ $endsAt }}" type="datetime-local" name="ends_at" id="ends_at" class="form-input">
                    @error('ends_at')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label for="status">{{ __('Status') }}</label>
                    <select name="status" id="status" class="form-input">
                        <option value="1" @selected(old('status', $d->status ?? 1) == 1)>{{ __('Enabled') }}</option>
                        <option value="0" @selected(old('status', $d->status ?? 1) == 0)>{{ __('Disabled') }}</option>
                    </select>
                    @error('status')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="deal-form-section deal-form-section--compact">
            <div class="deal-form-section__head">
                <span><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
                <div>
                    <p>{{ __('Action') }}</p>
                    <h4>{{ __('Call to action') }}</h4>
                </div>
            </div>

            <div class="deal-form-grid">
                <div class="form-field">
                    <label for="cta_text">{{ __('CTA text') }}</label>
                    <input value="{{ old('cta_text', $d->cta_text ?? '') }}" type="text" name="cta_text" id="cta_text"
                        class="form-input" placeholder="{{ __('Shop the deal') }}">
                    @error('cta_text')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="form-field deal-span-2">
                    <label for="cta_url">{{ __('CTA URL') }}</label>
                    <input value="{{ old('cta_url', $d->cta_url ?? '') }}" type="text" name="cta_url" id="cta_url"
                        class="form-input" placeholder="{{ __('/shop?sale=1') }}">
                    @error('cta_url')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="deal-form-section deal-form-section--compact deal-span-all">
            <div class="deal-form-section__head">
                <span><i class="fa-solid fa-boxes-stacked"></i></span>
                <div>
                    <p>{{ __('Merchandise') }}</p>
                    <h4>{{ __('Products in this deal') }}</h4>
                </div>
            </div>

            <div class="form-field"
                x-data="dealProductPicker(@js($products), @js($selectedIds))" @click.outside="open = false">
                <div class="picker__control deal-picker-control" @click="open = true">
                    <template x-for="p in chosen" :key="p.id">
                        <span class="picker__chip">
                            <template x-if="p.thumb"><img :src="p.thumb" alt=""></template>
                            <span x-text="p.name"></span>
                            <button type="button" @click.stop="remove(p.id)" aria-label="{{ __('Remove') }}"><i class="fa-solid fa-xmark"></i></button>
                        </span>
                    </template>
                    <input type="text" class="picker__search" x-model="query" @focus="open = true"
                        placeholder="{{ __('Search products to add...') }}">
                </div>
                <div class="picker__menu" x-show="open && results.length" x-cloak>
                    <template x-for="p in results" :key="p.id">
                        <button type="button" class="picker__opt" @click="add(p.id)">
                            <template x-if="p.thumb"><img :src="p.thumb" alt=""></template>
                            <template x-if="!p.thumb"><span class="picker__opt-ph"><i class="fa-solid fa-box"></i></span></template>
                            <span><span x-text="p.name"></span><small x-text="'$' + p.price"></small></span>
                        </button>
                    </template>
                </div>
                <template x-for="id in selected" :key="id"><input type="hidden" name="products[]" :value="id"></template>
                <p class="form-help mt-2"><span x-text="selected.length"></span> product(s) selected.</p>
                @error('products')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="deal-form-section deal-form-section--compact deal-span-all">
            <div class="deal-form-section__head">
                <span><i class="fa-solid fa-magnifying-glass-chart"></i></span>
                <div>
                    <p>{{ __('SEO') }}</p>
                    <h4>{{ __('Search metadata') }}</h4>
                </div>
            </div>

            <div class="deal-form-grid">
                <div class="form-field">
                    <label for="meta_title">{{ __('Meta title') }}</label>
                    <input value="{{ old('meta_title', $d->meta_title ?? '') }}" type="text" name="meta_title" id="meta_title" class="form-input">
                    @error('meta_title')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>

                <div class="form-field deal-span-2">
                    <label for="meta_description">{{ __('Meta description') }}</label>
                    <input value="{{ old('meta_description', $d->meta_description ?? '') }}" type="text" name="meta_description" id="meta_description" class="form-input">
                    @error('meta_description')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>
    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.deals.index') }}" class="form-cancel-button">{{ __('Cancel') }}</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>

@once
    @push('js')
        <script>
            window.dealProductPicker = (all, selected) => ({
                all: all,
                selected: selected,
                query: '',
                open: false,
                get chosen() {
                    return this.selected.map((id) => this.all.find((p) => p.id === id)).filter(Boolean);
                },
                get results() {
                    const q = this.query.trim().toLowerCase();
                    return this.all
                        .filter((p) => !this.selected.includes(p.id) && (q === '' || p.name.toLowerCase().includes(q)))
                        .slice(0, 10);
                },
                add(id) {
                    if (!this.selected.includes(id)) this.selected.push(id);
                    this.query = '';
                },
                remove(id) {
                    this.selected = this.selected.filter((x) => x !== id);
                },
            });
        </script>
    @endpush
@endonce
