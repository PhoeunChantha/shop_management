@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $selectedTagIds = old('tags', $isEdit ? $product->tags->pluck('id')->all() : []);

    // Product type + single-product fields.
    $productType = old('product_type', $isEdit ? $product->product_type->value : 'single');
    $singleSku = old('sku', $isEdit ? $product->sku : '');
    $singleStock = old('stock', $isEdit ? (string) $product->stock : '0');
    $singleLowStock = old('low_stock_alert', $isEdit ? (string) $product->low_stock_alert : '0');

    // Available attributes + values for the picker.
    $attributesJs = $attributes->map(fn ($attr) => [
        'id' => (string) $attr->id,
        'name' => $attr->name,
        'values' => $attr->values->map(fn ($v) => [
            'id' => (string) $v->id,
            'value' => $v->value,
            'color_hex' => $v->color_hex,
        ])->values(),
    ])->values();

    // value id => attribute id (to group selected values back under their attribute).
    $valueToAttr = [];
    foreach ($attributes as $attr) {
        foreach ($attr->values as $val) {
            $valueToAttr[(string) $val->id] = (string) $attr->id;
        }
    }

    // Seed variants from old() (after validation) or the saved model.
    $variantsSeed = old('variants');
    if ($variantsSeed === null && $isEdit) {
        $variantsSeed = $product->variants->map(fn ($v) => [
            'value_ids' => $v->values->pluck('id')->map(fn ($id) => (string) $id)->all(),
            'sku' => $v->sku,
            'barcode' => $v->barcode,
            'image' => $v->image,
            'image_url' => $v->image_url,
            'stock' => (string) $v->stock,
            'low_stock_alert' => (string) $v->low_stock_alert,
            'price' => $v->price !== null ? (string) $v->price : '',
            'cost_price' => $v->cost_price !== null ? (string) $v->cost_price : '',
            'weight' => $v->weight !== null ? (string) $v->weight : '',
            'status' => $v->status ? '1' : '0',
        ])->values()->all();
    }
    $variantsSeed = $variantsSeed ?: [];

    // Reconstruct the selected attributes + values from the variant value ids.
    $selectedGroups = [];
    foreach ($variantsSeed as $row) {
        foreach ((array) ($row['value_ids'] ?? []) as $vid) {
            $vid = (string) $vid;
            $aid = $valueToAttr[$vid] ?? null;
            if ($aid !== null) {
                $selectedGroups[$aid][$vid] = $vid;
            }
        }
    }
    $selectedSeed = [];
    foreach ($selectedGroups as $aid => $vids) {
        $selectedSeed[] = ['attributeId' => (string) $aid, 'valueIds' => array_values($vids)];
    }

    $specRows = old('specifications');
    if ($specRows === null && $isEdit) {
        $specRows = $product->specifications->map(fn ($s) => ['name' => $s->name, 'value' => $s->value])->values()->all();
    }
    $specRows = $specRows ?: [['name' => '', 'value' => '']];
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="product-form"
    x-data="{ lang: '{{ $primaryLang }}' }">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="product-form-grid">

        {{-- ===================== MAIN COLUMN ===================== --}}
        <div class="product-form-main">

            {{-- Language tabs — drive all translatable fields (Settings → Languages) --}}
            <div class="lang-tabs">
                <span class="lang-tabs__label">Content language</span>
                <div class="lang-tabs__buttons">
                    @foreach ($locales as $code => $label)
                        <button type="button" class="lang-tab" :class="{ 'is-active': lang === '{{ $code }}' }"
                            @click="lang = '{{ $code }}'">
                            <span>{{ $label }}</span>
                            <small>{{ strtoupper($code) }}</small>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- A. Basic Information --}}
            <section class="form-section">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-circle-info"></i></span>
                    <div><h4>Basic Information</h4><p>Customer-facing content and primary merchandising image.</p></div>
                    <span class="form-section__badge">Required</span>
                </div>
                <div class="form-section__body grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1 product-thumbnail-upload">
                        <x-image-upload name="thumbnail" label="Product thumbnail"
                            :value="\App\Helpers\ImageManager::path($product->thumbnail ?? null, 'products')"
                            accept="image/*" help="" />
                    </div>
                    <div class="md:col-span-2 d-flex flex-column gap-3">
                        {{-- Name (per language) --}}
                        @foreach ($locales as $code => $label)
                            <div class="form-field" x-show="lang === '{{ $code }}'" x-cloak>
                                <label>Product Name ({{ strtoupper($code) }}) @if ($code === $primaryLang)<span class="text-red-500">*</span>@endif</label>
                                <input type="text" name="name[{{ $code }}]" class="form-input"
                                    value="{{ old("name.$code", $isEdit ? $product->getTranslation('name', $code, false) : '') }}"
                                    @if ($code === $primaryLang) required @endif
                                    placeholder="e.g. Heavyweight Oversized Tee">
                                <p class="form-help">Keep it short and searchable. The primary language is required.</p>
                                @error("name.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                            </div>
                        @endforeach

                        {{-- Short Description (per language) --}}
                        @foreach ($locales as $code => $label)
                            <div class="form-field" x-show="lang === '{{ $code }}'" x-cloak>
                                <label>Short Description ({{ strtoupper($code) }})</label>
                                <input type="text" name="short_description[{{ $code }}]" class="form-input" maxlength="500"
                                    value="{{ old("short_description.$code", $isEdit ? $product->getTranslation('short_description', $code, false) : '') }}"
                                    placeholder="One-line summary shown on listings">
                                <p class="form-help">Useful for collection cards, quick previews and search snippets.</p>
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
                            <p class="form-help">Include fit, materials, care, and what makes this product different.</p>
                            @error("description.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- B. Product Gallery --}}
            <section class="form-section">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-images"></i></span>
                    <div><h4>Product Gallery</h4><p>Upload alternate angles, detail shots and campaign images.</p></div>
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
                    <div><h4>Pricing</h4><p>Selling price, margin inputs and optional promotion.</p></div>
                    <span class="form-section__badge">Storefront</span>
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
                            <p class="form-help">Base price used when a variant does not override it.</p>
                            @error('price')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-field">
                            <label for="cost_price">Cost Price ($)</label>
                            <input type="number" step="0.01" min="0" name="cost_price" id="cost_price" class="form-input"
                                value="{{ old('cost_price', $product->cost_price ?? '') }}" placeholder="0.00">
                            <p class="form-help">Internal cost for margin tracking.</p>
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

            {{-- E. Variants & Stock (ERP attribute workflow) --}}
            <section class="form-section"
                x-data="productVariants({
                    type: @js($productType),
                    attributes: @js($attributesJs),
                    selected: @js($selectedSeed),
                    variants: @js($variantsSeed),
                    single: {
                        sku: @js($singleSku),
                        stock: @js($singleStock),
                        low_stock_alert: @js($singleLowStock),
                    },
                })">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-layer-group"></i></span>
                    <div><h4>Variants &amp; Stock</h4><p>Choose a product type, then define stock — a single SKU or attribute-based variants.</p></div>
                    <span class="form-section__badge">Inventory</span>
                </div>
                <div class="form-section__body d-flex flex-column gap-4">

                    {{-- Product type --}}
                    <div class="form-field" style="max-width: 340px;">
                        <label>Product Type <span class="text-red-500">*</span></label>
                        <select class="form-input" name="product_type" x-model="type">
                            @foreach (\App\Enums\ProductType::options() as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('product_type')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    {{-- Single product --}}
                    <div class="variant-single-grid" x-show="type === 'single'" x-cloak>
                        <div class="form-field">
                            <label>SKU</label>
                            <input type="text" class="form-input" name="sku" x-model="single.sku"
                                :disabled="type !== 'single'" placeholder="Optional, must be unique">
                            @error('sku')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-field">
                            <label>Stock <span class="text-red-500">*</span></label>
                            <input type="number" min="0" class="form-input" name="stock" x-model="single.stock" :disabled="type !== 'single'">
                            @error('stock')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-field">
                            <label>Low Stock Alert</label>
                            <input type="number" min="0" class="form-input" name="low_stock_alert" x-model="single.low_stock_alert" :disabled="type !== 'single'">
                            @error('low_stock_alert')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Variable product --}}
                    <div x-show="type === 'variable'" x-cloak class="flex-column gap-4" style="display:flex;">

                        {{-- Attributes panel --}}
                        <div class="attr-panel">
                            <div class="attr-panel__head">
                                <div>
                                    <h5>Attributes</h5>
                                    <p>Add attributes like Size or Color, choose their values, then generate variants.</p>
                                </div>
                                <button type="button" class="attr-add-btn" @click="openAdd()">
                                    <i class="fa-solid fa-plus"></i> Add Attribute
                                </button>
                            </div>

                            <template x-if="selected.length === 0">
                                <div class="attr-empty">
                                    <i class="fa-solid fa-diagram-project"></i>
                                    <strong>No attributes selected</strong>
                                    <span>Add your first attribute to start building variants.</span>
                                </div>
                            </template>

                            <div class="attr-selected-list" x-show="selected.length > 0">
                                <template x-for="sel in selected" :key="sel.attributeId">
                                    <div class="attr-selected-card">
                                        <div class="attr-selected-card__main">
                                            <span class="attr-selected-card__name" x-text="attrName(sel.attributeId)"></span>
                                            <div class="attr-selected-card__values">
                                                <template x-for="vid in sel.valueIds" :key="vid">
                                                    <span class="attr-value-pill">
                                                        <template x-if="valueHex(vid)">
                                                            <span class="attr-swatch" :style="`background:${valueHex(vid)}`"></span>
                                                        </template>
                                                        <span x-text="valueName(vid)"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="attr-selected-card__actions">
                                            <button type="button" class="attr-mini-btn" @click="openEdit(sel)"><i class="fa-solid fa-pen"></i> Edit</button>
                                            <button type="button" class="attr-mini-btn attr-mini-btn--danger" @click="removeAttribute(sel.attributeId)"><i class="fa-solid fa-xmark"></i> Remove</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Generate --}}
                        <div class="attr-generate-bar" x-show="selected.length > 0">
                            <button type="button" class="attr-generate-btn" :disabled="!canGenerate()" @click="generate()">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                <span x-text="variants.length ? 'Regenerate Variants' : 'Generate Variants'"></span>
                            </button>
                            <span class="attr-generate-hint" x-show="dirty && variants.length" x-cloak>
                                <i class="fa-solid fa-triangle-exclamation"></i> Attributes changed — regenerate to update the table.
                            </span>
                        </div>

                        {{-- Variant table / matrix --}}
                        <div x-show="variants.length > 0" x-cloak class="flex-column gap-2" style="display:flex;">
                            <div class="variant-view-bar">
                                <span class="variant-count"><strong x-text="variants.length"></strong> variant<span x-text="variants.length === 1 ? '' : 's'"></span></span>
                                <div class="variant-view-toggle" x-show="canMatrix()">
                                    <button type="button" :class="{ 'is-on': view === 'table' }" @click="view = 'table'"><i class="fa-solid fa-table-list"></i> Table</button>
                                    <button type="button" :class="{ 'is-on': view === 'matrix' }" @click="view = 'matrix'"><i class="fa-solid fa-table-cells"></i> Matrix</button>
                                </div>
                            </div>

                            {{-- TABLE VIEW (holds the authoritative submit inputs) --}}
                            <div class="erp-table-wrap" x-show="view === 'table' || !canMatrix()">
                                <table class="erp-table">
                                    <thead>
                                        <tr>
                                            <th>Image</th><th>Variant</th><th>SKU</th><th>Barcode</th><th>Price</th>
                                            <th>Cost</th><th>Stock</th><th>Low</th><th>Status</th><th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(v, i) in variants" :key="v.value_ids.join('-')">
                                            <tr>
                                                <td>
                                                    <input type="hidden" :name="`variants[${i}][image_existing]`" :value="v.image" :disabled="type !== 'variable'">
                                                    <label class="erp-img" title="Upload variant image">
                                                        <template x-if="v.preview"><img :src="v.preview" alt=""></template>
                                                        <template x-if="!v.preview"><i class="fa-regular fa-image"></i></template>
                                                        <input type="file" accept="image/*" class="visually-hidden"
                                                            :name="`variants[${i}][image]`" @change="pickVariantImage($event, v)" :disabled="type !== 'variable'">
                                                    </label>
                                                </td>
                                                <td class="erp-variant-cell">
                                                    <template x-for="vid in v.value_ids" :key="vid">
                                                        <input type="hidden" :name="`variants[${i}][value_ids][]`" :value="vid" :disabled="type !== 'variable'">
                                                    </template>
                                                    <input type="hidden" :name="`variants[${i}][weight]`" :value="v.weight" :disabled="type !== 'variable'">
                                                    <span class="erp-variant-badge" x-text="variantLabel(v)"></span>
                                                </td>
                                                <td><input type="text" class="form-input" placeholder="Auto" :name="`variants[${i}][sku]`" x-model="v.sku" :disabled="type !== 'variable'"></td>
                                                <td><input type="text" class="form-input" placeholder="—" :name="`variants[${i}][barcode]`" x-model="v.barcode" :disabled="type !== 'variable'"></td>
                                                <td><input type="number" step="0.01" min="0" class="form-input" placeholder="Base" :name="`variants[${i}][price]`" x-model="v.price" :disabled="type !== 'variable'"></td>
                                                <td><input type="number" step="0.01" min="0" class="form-input" placeholder="—" :name="`variants[${i}][cost_price]`" x-model="v.cost_price" :disabled="type !== 'variable'"></td>
                                                <td><input type="number" min="0" class="form-input" :name="`variants[${i}][stock]`" x-model="v.stock" :disabled="type !== 'variable'"></td>
                                                <td><input type="number" min="0" class="form-input" :name="`variants[${i}][low_stock_alert]`" x-model="v.low_stock_alert" :disabled="type !== 'variable'"></td>
                                                <td>
                                                    <select class="form-input" :name="`variants[${i}][status]`" x-model="v.status" :disabled="type !== 'variable'">
                                                        <option value="1">Active</option>
                                                        <option value="0">Inactive</option>
                                                    </select>
                                                </td>
                                                <td><button type="button" class="erp-row-del" @click="removeVariant(i)" title="Remove"><i class="fa-solid fa-trash"></i></button></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            {{-- MATRIX VIEW (edits the same variant objects; stock only) --}}
                            <div class="erp-matrix-wrap" x-show="view === 'matrix' && canMatrix()" x-cloak>
                                <table class="erp-matrix">
                                    <thead>
                                        <tr>
                                            <th class="erp-matrix__corner">
                                                <span x-text="attrName(selected[1].attributeId)"></span>
                                                <i class="fa-solid fa-arrow-right-long"></i>
                                            </th>
                                            <template x-for="colId in colValues()" :key="colId">
                                                <th><span x-text="valueName(colId)"></span></th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="rowId in rowValues()" :key="rowId">
                                            <tr>
                                                <th class="erp-matrix__rowhead" x-text="valueName(rowId)"></th>
                                                <template x-for="colId in colValues()" :key="colId">
                                                    <td>
                                                        <template x-if="matrixVariant(rowId, colId)">
                                                            <input type="number" min="0" class="form-input erp-matrix__cell"
                                                                x-model="matrixVariant(rowId, colId).stock">
                                                        </template>
                                                    </td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                                <p class="form-help mt-2">Matrix edits stock only. Switch to Table for SKU, price and status.</p>
                            </div>
                        </div>
                    </div>

                    @foreach ($errors->keys() as $key)
                        @if (str_starts_with($key, 'variants') || in_array($key, ['stock', 'sku', 'product_type', 'low_stock_alert']))
                            <p class="text-red-500 text-sm mt-1.5">{{ $errors->first($key) }}</p>
                        @endif
                    @endforeach

                    {{-- Add / Edit Attribute modal --}}
                    <template x-teleport="body">
                        <div class="modal-backdrop-premium" x-show="modalOpen" x-cloak
                            @keydown.escape.window="closeModal()" @click.self="closeModal()"
                            x-transition.opacity.duration.150ms style="display:none;">
                            <div class="attr-modal">
                                <div class="attr-modal__head">
                                    <div>
                                        <h3 x-text="modalMode === 'edit' ? 'Edit Attribute' : 'Add Attribute'"></h3>
                                        <p>Select an attribute and the values this product offers.</p>
                                    </div>
                                    <button type="button" class="form-modal__close" @click="closeModal()"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                                <div class="attr-modal__body">
                                    <div class="form-field">
                                        <label>Attribute</label>
                                        <select class="form-input" x-model="draftAttributeId" @change="onDraftAttributeChange()" :disabled="modalMode === 'edit'">
                                            <option value="">Select attribute…</option>
                                            <template x-for="a in availableAttributes()" :key="a.id">
                                                <option :value="a.id" x-text="a.name"></option>
                                            </template>
                                        </select>
                                        <template x-if="attributes.length === 0">
                                            <p class="form-help mt-1">No attributes exist yet — create some under Catalog → Attributes.</p>
                                        </template>
                                    </div>

                                    <div x-show="draftAttributeId" x-cloak class="attr-modal__values">
                                        <div class="attr-value-search">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                            <input type="text" x-model="valueSearch" placeholder="Search values…">
                                        </div>
                                        <div class="attr-check-list">
                                            <template x-for="v in draftValues()" :key="v.id">
                                                <label class="attr-check" :class="{ 'is-on': draftValueIds.includes(v.id) }">
                                                    <input type="checkbox" class="visually-hidden" :checked="draftValueIds.includes(v.id)" @change="toggleDraftValue(v.id)">
                                                    <span class="attr-check__box"><i class="fa-solid fa-check"></i></span>
                                                    <template x-if="v.color_hex"><span class="attr-swatch" :style="`background:${v.color_hex}`"></span></template>
                                                    <span x-text="v.value"></span>
                                                </label>
                                            </template>
                                            <template x-if="draftAttributeId && draftValues().length === 0">
                                                <p class="text-sm text-gray-400 p-2">No values match.</p>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div class="attr-modal__foot">
                                    <button type="button" class="modal-cancel" @click="closeModal()">Cancel</button>
                                    <button type="button" class="form-submit-button" :disabled="!draftAttributeId || draftValueIds.length === 0" @click="saveModal()">
                                        <i class="fa-solid fa-check"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            {{-- F. Specifications --}}
            <section class="form-section"
                x-data="{ specs: @js($specRows), add() { this.specs.push({ name: '', value: '' }); }, remove(i) { this.specs.splice(i, 1); if (this.specs.length === 0) this.add(); } }">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-list-check"></i></span>
                    <div><h4>Specifications</h4><p>Shipping weight and structured product attributes.</p></div>
                </div>
                <div class="form-section__body">
                    <div class="spec-grid">
                        <div class="form-field">
                            <label for="weight">Product Weight (kg)</label>
                            <input type="number" step="0.01" min="0" name="weight" id="weight" class="form-input"
                                value="{{ old('weight', $product->weight ?? '') }}" placeholder="0.00">
                            <p class="form-help">Optional shipping weight for fulfillment and carrier estimates.</p>
                            @error('weight')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>

                        <div class="spec-note">
                            <strong>Recommended specs</strong>
                            <span>Material, fit, care instructions, origin, package contents.</span>
                        </div>
                    </div>

                    <div class="dynamic-field-header mt-4">
                        <label>Specifications <span class="text-gray-400 font-normal">(e.g. Material → 100% Cotton)</span></label>
                        <button type="button" class="dynamic-add-button" @click="add()"><i class="fa-solid fa-plus"></i> Add row</button>
                    </div>
                    <div class="spec-row-list mt-2">
                        <template x-for="(row, i) in specs" :key="i">
                            <div class="spec-row">
                                <input type="text" class="form-input" x-model="row.name" :name="`specifications[${i}][name]`" placeholder="Name (e.g. Material)">
                                <input type="text" class="form-input" x-model="row.value" :name="`specifications[${i}][value]`" placeholder="Value (e.g. 100% Cotton)">
                                <button type="button" class="dynamic-remove-button" @click="remove(i)"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            {{-- H. SEO --}}
            <section class="form-section">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-magnifying-glass-chart"></i></span>
                    <div><h4>SEO</h4><p>Search engine title and description per language.</p></div>
                </div>
                <div class="form-section__body d-flex flex-column gap-3">
                    @foreach ($locales as $code => $label)
                        <div class="form-field" x-show="lang === '{{ $code }}'" x-cloak>
                            <label>SEO Title ({{ strtoupper($code) }})</label>
                            <input type="text" name="seo_title[{{ $code }}]" class="form-input" maxlength="255"
                                value="{{ old("seo_title.$code", $isEdit ? $product->getTranslation('seo_title', $code, false) : '') }}"
                                placeholder="Meta title">
                            <p class="form-help">Aim for 50-60 characters. Leave blank to let the product name carry search.</p>
                            @error("seo_title.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-field" x-show="lang === '{{ $code }}'" x-cloak>
                            <label>SEO Description ({{ strtoupper($code) }})</label>
                            <textarea name="seo_description[{{ $code }}]" class="form-input" rows="2" maxlength="500"
                                placeholder="Meta description">{{ old("seo_description.$code", $isEdit ? $product->getTranslation('seo_description', $code, false) : '') }}</textarea>
                            <p class="form-help">Summarize benefits, material and audience in one concise sentence.</p>
                            @error("seo_description.$code")<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- ===================== SIDEBAR ===================== --}}
        <aside class="product-form-side">

            {{-- I. Publishing --}}
            <section class="form-section form-section--side">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-rocket"></i></span>
                    <div><h4>Publishing</h4><p>Control availability and merchandising flags.</p></div>
                </div>
                <div class="form-section__body d-flex flex-column gap-3">
                    <div class="form-field">
                        <label for="status">Status <span class="text-red-500">*</span></label>
                        <select name="status" id="status" class="form-input">
                            @foreach (['draft' => 'Draft', 'active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('status', $product->status ?? 'draft') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="form-help">Draft products stay hidden until published.</p>
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
            <section class="form-section form-section--side">
                <div class="form-section__head">
                    <span class="form-section__icon"><i class="fa-solid fa-sitemap"></i></span>
                    <div><h4>Organization</h4><p>Catalog placement, brand and tags.</p></div>
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
                        <p class="form-help">Required for storefront navigation and filters.</p>
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
                        <p class="form-help">Tags improve admin search, campaigns and product grouping.</p>
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

@once
    @push('js')
        <script>
            window.productVariants = function (config) {
                return {
                    type: config.type || 'single',
                    single: config.single || { sku: '', stock: '0', low_stock_alert: '0' },
                    attributes: config.attributes || [],   // [{id,name,values:[{id,value,color_hex}]}]
                    selected: config.selected || [],        // [{attributeId, valueIds:[]}]
                    variants: [],
                    view: 'table',
                    generated: false,
                    dirty: false,

                    // modal state
                    modalOpen: false,
                    modalMode: 'add',
                    draftAttributeId: '',
                    draftValueIds: [],
                    valueSearch: '',

                    // lookups
                    _valueMap: {},
                    _attrMap: {},

                    init() {
                        this.attributes.forEach((a) => {
                            this._attrMap[a.id] = a;
                            a.values.forEach((v) => { this._valueMap[v.id] = { ...v, attributeId: a.id }; });
                        });

                        this.variants = (config.variants || []).map((v) => ({
                            value_ids: (v.value_ids || []).map(String),
                            sku: v.sku || '', barcode: v.barcode || '',
                            image: v.image || '', preview: v.image_url || null,
                            stock: v.stock ?? '0', low_stock_alert: v.low_stock_alert ?? '0',
                            price: v.price ?? '', cost_price: v.cost_price ?? '', weight: v.weight ?? '',
                            status: v.status ?? '1',
                        }));
                        this.generated = this.variants.length > 0;
                    },

                    pickVariantImage(e, v) {
                        const file = e.target.files[0];
                        if (file) v.preview = URL.createObjectURL(file);
                    },

                    /* ---- attribute / value helpers ---- */
                    attrName(id) { return this._attrMap[id]?.name || ''; },
                    valueName(id) { return this._valueMap[id]?.value || ''; },
                    valueHex(id) { return this._valueMap[id]?.color_hex || null; },
                    isSelected(attrId) { return this.selected.some((s) => s.attributeId === attrId); },
                    availableAttributes() {
                        return this.attributes.filter((a) => !this.isSelected(a.id) || a.id === this.draftAttributeId);
                    },
                    draftAttribute() { return this._attrMap[this.draftAttributeId] || null; },
                    draftValues() {
                        const a = this.draftAttribute();
                        if (!a) return [];
                        const q = this.valueSearch.trim().toLowerCase();
                        return q ? a.values.filter((v) => v.value.toLowerCase().includes(q)) : a.values;
                    },
                    toggleDraftValue(id) {
                        const i = this.draftValueIds.indexOf(id);
                        if (i === -1) this.draftValueIds.push(id); else this.draftValueIds.splice(i, 1);
                    },

                    /* ---- modal ---- */
                    openAdd() {
                        this.modalMode = 'add'; this.draftAttributeId = ''; this.draftValueIds = []; this.valueSearch = '';
                        this.modalOpen = true;
                    },
                    openEdit(sel) {
                        this.modalMode = 'edit'; this.draftAttributeId = sel.attributeId;
                        this.draftValueIds = [...sel.valueIds]; this.valueSearch = '';
                        this.modalOpen = true;
                    },
                    closeModal() { this.modalOpen = false; },
                    onDraftAttributeChange() { this.draftValueIds = []; this.valueSearch = ''; },
                    saveModal() {
                        if (!this.draftAttributeId || this.draftValueIds.length === 0) return;
                        const existing = this.selected.find((s) => s.attributeId === this.draftAttributeId);
                        if (existing) existing.valueIds = [...this.draftValueIds];
                        else this.selected.push({ attributeId: this.draftAttributeId, valueIds: [...this.draftValueIds] });
                        this.dirty = true;
                        this.closeModal();
                    },
                    removeAttribute(attrId) {
                        this.selected = this.selected.filter((s) => s.attributeId !== attrId);
                        this.dirty = true;
                    },

                    /* ---- generate ---- */
                    canGenerate() {
                        return this.selected.length > 0 && this.selected.every((s) => s.valueIds.length > 0);
                    },
                    keyOf(ids) { return ids.map(Number).sort((a, b) => a - b).join('-'); },
                    cartesian(arrays) {
                        return arrays.reduce((acc, arr) => acc.flatMap((a) => arr.map((b) => [...a, b])), [[]]);
                    },
                    generate() {
                        if (!this.canGenerate()) return;
                        const combos = this.cartesian(this.selected.map((s) => s.valueIds));
                        const existing = {};
                        this.variants.forEach((v) => { existing[this.keyOf(v.value_ids)] = v; });
                        this.variants = combos.map((combo) => {
                            const key = this.keyOf(combo);
                            if (existing[key]) { existing[key].value_ids = combo; return existing[key]; }
                            return { value_ids: combo, sku: '', barcode: '', image: '', preview: null, stock: '0', low_stock_alert: '0', price: '', cost_price: '', weight: '', status: '1' };
                        });
                        this.generated = true; this.dirty = false;
                        if (!this.canMatrix()) this.view = 'table';
                    },
                    removeVariant(i) { this.variants.splice(i, 1); },
                    variantLabel(v) {
                        const order = this.selected.map((s) => s.attributeId);
                        const sorted = [...v.value_ids].sort((a, b) =>
                            order.indexOf(this._valueMap[a]?.attributeId) - order.indexOf(this._valueMap[b]?.attributeId));
                        return sorted.map((id) => this.valueName(id)).join(' / ');
                    },

                    /* ---- matrix ---- */
                    canMatrix() { return this.selected.length === 2 && this.variants.length > 0; },
                    rowValues() { return this.selected[0]?.valueIds || []; },
                    colValues() { return this.selected[1]?.valueIds || []; },
                    matrixVariant(rowId, colId) {
                        return this.variants.find((v) => v.value_ids.includes(rowId) && v.value_ids.includes(colId));
                    },
                };
            };
        </script>
    @endpush
@endonce
