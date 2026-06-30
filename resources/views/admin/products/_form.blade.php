@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $selectedTagIds = old('tags', $isEdit ? $product->tags->pluck('id')->all() : []);

    // Seed Alpine repeaters from old input (after validation) or the saved model.
    $variantRows = old('variants');
    if ($variantRows === null && $isEdit) {
        $variantRows = $product->variants->map(fn ($v) => [
            'size_id' => (string) $v->size_id,
            'color_id' => (string) $v->color_id,
            'sku' => $v->sku,
            'barcode' => $v->barcode,
            'stock' => (string) $v->stock,
            'low_stock_alert' => (string) $v->low_stock_alert,
            'price' => $v->price !== null ? (string) $v->price : '',
            'cost_price' => $v->cost_price !== null ? (string) $v->cost_price : '',
            'weight' => $v->weight !== null ? (string) $v->weight : '',
            'status' => $v->status ? '1' : '0',
        ])->values()->all();
    }
    $variantRows = $variantRows ?: [];

    $specRows = old('specifications');
    if ($specRows === null && $isEdit) {
        $specRows = $product->specifications->map(fn ($s) => ['name' => $s->name, 'value' => $s->value])->values()->all();
    }
    $specRows = $specRows ?: [['name' => '', 'value' => '']];
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="product-form"
    x-data="{
        lang: '{{ $primaryLang }}',
        slug: @js(old('slug', $product->slug ?? '')),
        slugify(s) { return (s || '').toString().toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); }
    }">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="product-form-grid">

        {{-- ===================== MAIN COLUMN ===================== --}}
        <div class="product-form-main">

            {{-- Language tabs — drive all translatable fields (Settings → Languages) --}}
            <div class="lang-tabs">
                @foreach ($locales as $code => $label)
                    <button type="button" class="lang-tab" :class="{ 'is-active': lang === '{{ $code }}' }"
                        @click="lang = '{{ $code }}'">{{ $label }}</button>
                @endforeach
            </div>

            {{-- A. Basic Information --}}
            <section class="form-section">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-circle-info"></i></span>
                    <div><h4>Basic Information</h4><p>Thumbnail, name and description.</p></div>
                </div>
                <div class="form-section__body grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1">
                        <x-image-upload name="thumbnail" label="Thumbnail"
                            :value="\App\Helpers\ImageManager::path($product->thumbnail ?? null, 'products')"
                            accept="image/*" help="Main image · up to 2MB" />
                    </div>
                    <div class="md:col-span-2 d-flex flex-column gap-3">
                        {{-- Name (per language) --}}
                        @foreach ($locales as $code => $label)
                            <div class="form-field" x-show="lang === '{{ $code }}'" x-cloak>
                                <label>Product Name ({{ strtoupper($code) }}) @if ($code === $primaryLang)<span class="text-red-500">*</span>@endif</label>
                                <input type="text" name="name[{{ $code }}]" class="form-input"
                                    value="{{ old("name.$code", $isEdit ? $product->getTranslation('name', $code, false) : '') }}"
                                    @if ($code === $primaryLang) @input="slug = slugify($event.target.value)" required @endif
                                    placeholder="e.g. Heavyweight Oversized Tee">
                                @error("name.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                            </div>
                        @endforeach

                        <div class="form-field">
                            <label>Slug <span class="text-gray-400 font-normal">(auto from {{ strtoupper($primaryLang) }} name)</span></label>
                            <input type="text" class="form-input" :value="slug" readonly placeholder="auto-generated" style="opacity:.75;">
                        </div>

                        {{-- Short Description (per language) --}}
                        @foreach ($locales as $code => $label)
                            <div class="form-field" x-show="lang === '{{ $code }}'" x-cloak>
                                <label>Short Description ({{ strtoupper($code) }})</label>
                                <input type="text" name="short_description[{{ $code }}]" class="form-input" maxlength="500"
                                    value="{{ old("short_description.$code", $isEdit ? $product->getTranslation('short_description', $code, false) : '') }}"
                                    placeholder="One-line summary shown on listings">
                                @error("short_description.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>

                    {{-- Description (per language) --}}
                    @foreach ($locales as $code => $label)
                        <div class="form-field md:col-span-3" x-show="lang === '{{ $code }}'" x-cloak>
                            <label>Description ({{ strtoupper($code) }})</label>
                            <textarea name="description[{{ $code }}]" class="form-input" rows="5"
                                placeholder="Full product description...">{{ old("description.$code", $isEdit ? $product->getTranslation('description', $code, false) : '') }}</textarea>
                            @error("description.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- B. Product Gallery --}}
            <section class="form-section">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-images"></i></span>
                    <div><h4>Product Gallery</h4><p>Upload multiple images. Pick one as primary.</p></div>
                </div>
                <div class="form-section__body">
                    <x-admin::multiple-image-upload name="images" :existing="$product->images ?? null" />
                    @error('images.*')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>
            </section>

            {{-- D. Pricing --}}
            <section class="form-section">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-tags"></i></span>
                    <div><h4>Pricing</h4><p>Selling price, cost and discount.</p></div>
                </div>
                <div class="form-section__body"
                    x-data="{
                        price: {{ (float) old('price', $product->price ?? 0) }},
                        type: @js(old('discount_type', $product->discount_type ?? '')),
                        amount: {{ (float) old('discount_amount', $product->discount_amount ?? 0) }},
                        get final() {
                            let p = parseFloat(this.price) || 0, a = parseFloat(this.amount) || 0, f = p;
                            if (this.type === 'fixed') f = p - a;
                            else if (this.type === 'percentage') f = p - (p * a / 100);
                            return Math.max(0, f).toFixed(2);
                        }
                    }">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="form-field">
                            <label for="price">Selling Price ($) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" name="price" id="price" class="form-input"
                                x-model="price" value="{{ old('price', $product->price ?? '') }}" required>
                            @error('price')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-field">
                            <label for="cost_price">Cost Price ($)</label>
                            <input type="number" step="0.01" min="0" name="cost_price" id="cost_price" class="form-input"
                                value="{{ old('cost_price', $product->cost_price ?? '') }}" placeholder="0.00">
                            @error('cost_price')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-field">
                            <label for="discount_type">Discount Type</label>
                            <select name="discount_type" id="discount_type" class="form-input" x-model="type">
                                <option value="">None</option>
                                <option value="fixed">Fixed ($)</option>
                                <option value="percentage">Percentage (%)</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="discount_amount">Discount Amount</label>
                            <input type="number" step="0.01" min="0" name="discount_amount" id="discount_amount"
                                class="form-input" x-model="amount" :disabled="type === ''"
                                value="{{ old('discount_amount', $product->discount_amount ?? '') }}">
                            @error('discount_amount')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="price-preview">
                        <span>Final price after discount</span>
                        <strong x-text="'$' + final"></strong>
                    </div>
                </div>
            </section>

            {{-- E. Variants & Stock --}}
            <section class="form-section"
                x-data="{
                    variants: @js($variantRows),
                    add() { this.variants.push({ size_id: '', color_id: '', sku: '', barcode: '', stock: '0', low_stock_alert: '0', price: '', cost_price: '', weight: '', status: '1' }); },
                    remove(i) { this.variants.splice(i, 1); }
                }">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-layer-group"></i></span>
                    <div><h4>Variants &amp; Stock</h4><p>Size, color, SKU, barcode, stock and pricing per variant.</p></div>
                    <button type="button" class="dynamic-add-button ms-auto" @click="add()"><i class="fa-solid fa-plus"></i> Add variant</button>
                </div>
                <div class="form-section__body">
                    <template x-if="variants.length === 0">
                        <p class="text-sm text-gray-400 dark:text-slate-500">No variants yet — click “Add variant”. SKU must be unique across all products.</p>
                    </template>
                    <div class="d-flex flex-column gap-3">
                        <template x-for="(row, i) in variants" :key="i">
                            <div class="variant-card">
                                <div class="variant-card__grid">
                                    <div class="form-field">
                                        <label>Size</label>
                                        <select class="form-input" x-model="row.size_id" :name="`variants[${i}][size_id]`">
                                            <option value="">Size</option>
                                            @foreach ($sizes as $size)
                                                <option value="{{ $size->id }}">{{ $size->name }}{{ $size->code ? " ({$size->code})" : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label>Color</label>
                                        <select class="form-input" x-model="row.color_id" :name="`variants[${i}][color_id]`">
                                            <option value="">Color</option>
                                            @foreach ($colors as $color)
                                                <option value="{{ $color->id }}">{{ $color->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label>SKU</label>
                                        <input type="text" class="form-input" x-model="row.sku" :name="`variants[${i}][sku]`" placeholder="SKU-001">
                                    </div>
                                    <div class="form-field">
                                        <label>Barcode</label>
                                        <input type="text" class="form-input" x-model="row.barcode" :name="`variants[${i}][barcode]`" placeholder="Barcode">
                                    </div>
                                    <div class="form-field">
                                        <label>Stock</label>
                                        <input type="number" min="0" class="form-input" x-model="row.stock" :name="`variants[${i}][stock]`">
                                    </div>
                                    <div class="form-field">
                                        <label>Low Stock Alert</label>
                                        <input type="number" min="0" class="form-input" x-model="row.low_stock_alert" :name="`variants[${i}][low_stock_alert]`">
                                    </div>
                                    <div class="form-field">
                                        <label>Price ($)</label>
                                        <input type="number" step="0.01" min="0" class="form-input" x-model="row.price" :name="`variants[${i}][price]`" placeholder="Product price">
                                    </div>
                                    <div class="form-field">
                                        <label>Cost Price ($)</label>
                                        <input type="number" step="0.01" min="0" class="form-input" x-model="row.cost_price" :name="`variants[${i}][cost_price]`">
                                    </div>
                                    <div class="form-field">
                                        <label>Weight (kg)</label>
                                        <input type="number" step="0.01" min="0" class="form-input" x-model="row.weight" :name="`variants[${i}][weight]`">
                                    </div>
                                    <div class="form-field">
                                        <label>Status</label>
                                        <select class="form-input" x-model="row.status" :name="`variants[${i}][status]`">
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="button" class="variant-card__remove" @click="remove(i)" title="Remove variant"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </template>
                    </div>
                    @foreach ($errors->keys() as $key)
                        @if (str_starts_with($key, 'variants.'))
                            <p class="text-red-500 text-sm mt-1.5">{{ $errors->first($key) }}</p>
                        @endif
                    @endforeach
                </div>
            </section>

            {{-- F. Shipping + G. Specifications --}}
            <section class="form-section"
                x-data="{ specs: @js($specRows), add() { this.specs.push({ name: '', value: '' }); }, remove(i) { this.specs.splice(i, 1); if (this.specs.length === 0) this.add(); } }">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-list-check"></i></span>
                    <div><h4>Shipping &amp; Specifications</h4><p>Product weight and key spec rows.</p></div>
                </div>
                <div class="form-section__body">
                    <div class="form-field" style="max-width:220px;">
                        <label for="weight">Product Weight (kg)</label>
                        <input type="number" step="0.01" min="0" name="weight" id="weight" class="form-input"
                            value="{{ old('weight', $product->weight ?? '') }}" placeholder="0.00">
                        @error('weight')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div class="dynamic-field-header mt-4">
                        <label>Specifications <span class="text-gray-400 font-normal">(e.g. Material → 100% Cotton)</span></label>
                        <button type="button" class="dynamic-add-button" @click="add()"><i class="fa-solid fa-plus"></i> Add row</button>
                    </div>
                    <div class="d-flex flex-column gap-2 mt-2">
                        <template x-for="(row, i) in specs" :key="i">
                            <div class="grid grid-cols-12 gap-2">
                                <input type="text" class="form-input col-span-5" x-model="row.name" :name="`specifications[${i}][name]`" placeholder="Name (e.g. Material)">
                                <input type="text" class="form-input col-span-6" x-model="row.value" :name="`specifications[${i}][value]`" placeholder="Value (e.g. 100% Cotton)">
                                <button type="button" class="dynamic-remove-button col-span-1" @click="remove(i)"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            {{-- H. SEO --}}
            <section class="form-section">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-magnifying-glass-chart"></i></span>
                    <div><h4>SEO</h4><p>Search engine title and description.</p></div>
                </div>
                <div class="form-section__body d-flex flex-column gap-3">
                    @foreach ($locales as $code => $label)
                        <div x-show="lang === '{{ $code }}'" x-cloak class="d-flex flex-column gap-3">
                            <div class="form-field">
                                <label>SEO Title ({{ strtoupper($code) }})</label>
                                <input type="text" name="seo_title[{{ $code }}]" class="form-input" maxlength="255"
                                    value="{{ old("seo_title.$code", $isEdit ? $product->getTranslation('seo_title', $code, false) : '') }}"
                                    placeholder="Meta title">
                                @error("seo_title.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-field">
                                <label>SEO Description ({{ strtoupper($code) }})</label>
                                <textarea name="seo_description[{{ $code }}]" class="form-input" rows="2" maxlength="500"
                                    placeholder="Meta description">{{ old("seo_description.$code", $isEdit ? $product->getTranslation('seo_description', $code, false) : '') }}</textarea>
                                @error("seo_description.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- ===================== SIDEBAR ===================== --}}
        <aside class="product-form-side">

            {{-- I. Publishing --}}
            <section class="form-section">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-rocket"></i></span>
                    <div><h4>Publishing</h4></div>
                </div>
                <div class="form-section__body d-flex flex-column gap-3">
                    <div class="form-field">
                        <label for="status">Status <span class="text-red-500">*</span></label>
                        <select name="status" id="status" class="form-input">
                            @foreach (['draft' => 'Draft', 'active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('status', $product->status ?? 'draft') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @php($flags = ['is_featured' => 'Featured', 'is_new' => 'New Arrival', 'is_best_seller' => 'Best Seller', 'is_on_sale' => 'On Sale'])
                    <div class="d-flex flex-column gap-2">
                        @foreach ($flags as $field => $label)
                            <label class="product-toggle">
                                <span>{{ $label }}</span>
                                <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $product->$field ?? false))>
                                <i></i>
                            </label>
                        @endforeach
                    </div>

                    <div class="form-field">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" min="0" name="sort_order" id="sort_order" class="form-input"
                            value="{{ old('sort_order', $product->sort_order ?? 0) }}">
                    </div>
                </div>
            </section>

            {{-- C. Organization --}}
            <section class="form-section">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-sitemap"></i></span>
                    <div><h4>Organization</h4></div>
                </div>
                <div class="form-section__body d-flex flex-column gap-3">
                    <div class="form-field">
                        <label for="category_id">Category <span class="text-red-500">*</span></label>
                        <select name="category_id" id="category_id" class="form-input" required>
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field">
                        <label for="sub_category_id">Sub Category</label>
                        <select name="sub_category_id" id="sub_category_id" class="form-input">
                            <option value="">None</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('sub_category_id', $product->sub_category_id ?? '') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="brand_id">Brand</label>
                        <select name="brand_id" id="brand_id" class="form-input">
                            <option value="">None</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Tags</label>
                        <div class="tag-chips">
                            @foreach ($tags as $tag)
                                <label class="tag-chip">
                                    <input type="checkbox" name="tags[]" value="{{ $tag->id }}" @checked(in_array($tag->id, $selectedTagIds))>
                                    <span>{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <input type="text" name="new_tags" class="form-input mt-2" placeholder="Add new tags, comma separated"
                            value="{{ old('new_tags') }}">
                    </div>
                </div>
            </section>

            {{-- Actions --}}
            <div class="form-section product-form-actions">
                <a href="{{ route('admin.products.index') }}" class="form-cancel-button">Cancel</a>
                @if ($isEdit)
                    <button type="submit" class="form-submit-button"><i class="fa-solid fa-check"></i> {{ __('Update Product') }}</button>
                @else
                    <button type="submit" class="btn-light" onclick="document.getElementById('status').value='draft';">
                        <i class="fa-solid fa-file-lines"></i> Save Draft
                    </button>
                    <button type="submit" class="form-submit-button" onclick="document.getElementById('status').value='active';">
                        <i class="fa-solid fa-rocket"></i> Publish Product
                    </button>
                @endif
            </div>
        </aside>
    </div>
</form>
