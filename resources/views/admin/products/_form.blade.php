@php
    $isEdit = ($mode ?? 'create') === 'edit';

    // Seed the Alpine variant repeater from old input (validation) or the saved
    // product, falling back to a single empty row.
    $variantRows = old('variants');
    if ($variantRows === null && $isEdit) {
        $variantRows = $product->variants
            ->map(fn ($v) => [
                'size_id' => (string) $v->size_id,
                'color_id' => (string) $v->color_id,
                'sku' => $v->sku,
                'stock' => (string) $v->stock,
                'price' => $v->price !== null ? (string) $v->price : '',
            ])->values()->all();
    }
    $variantRows = $variantRows ?: [['size_id' => '', 'color_id' => '', 'sku' => '', 'stock' => '0', 'price' => '']];
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Name --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="name">Product Name <span class="text-red-500">*</span></label>
            <input value="{{ old('name', $product->name ?? '') }}" type="text" name="name" id="name"
                class="form-input" placeholder="e.g. Heavyweight Oversized Tee" required>
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">The URL slug is generated automatically from the name.</small>
            @error('name')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        {{-- Status --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status <span class="text-red-500">*</span></label>
            <select name="status" id="status" class="form-input">
                <option value="active" @selected(old('status', $product->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $product->status ?? 'active') === 'inactive')>Inactive</option>
            </select>
            @error('status')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        {{-- Category --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="category_id">Category <span class="text-red-500">*</span></label>
            <select name="category_id" id="category_id" class="form-input" required>
                <option value="">Select category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        {{-- Sub category --}}
        <div class="form-field col-span-2 md:col-span-1">
            <label for="sub_category_id">Sub Category <span class="text-gray-400 font-normal">(optional)</span></label>
            <select name="sub_category_id" id="sub_category_id" class="form-input">
                <option value="">None</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('sub_category_id', $product->sub_category_id ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('sub_category_id')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        {{-- Description --}}
        <div class="form-field col-span-2">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-input" rows="3"
                placeholder="Describe the product...">{{ old('description', $product->description ?? '') }}</textarea>
            @error('description')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        {{-- Price + discount (live final-price preview) --}}
        <div class="col-span-2" x-data="{
            price: {{ (float) old('price', $product->price ?? 0) }},
            type: '{{ old('discount_type', $product->discount_type ?? '') }}',
            amount: {{ (float) old('discount_amount', $product->discount_amount ?? 0) }},
            get final() {
                let p = parseFloat(this.price) || 0;
                let a = parseFloat(this.amount) || 0;
                let f = p;
                if (this.type === 'fixed') f = p - a;
                else if (this.type === 'percentage') f = p - (p * a / 100);
                return Math.max(0, f).toFixed(2);
            }
        }">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-field">
                    <label for="price">Price ($) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="price" id="price" class="form-input"
                        x-model="price" value="{{ old('price', $product->price ?? '') }}" placeholder="0.00" required>
                    @error('price')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="discount_type">Discount Type</label>
                    <select name="discount_type" id="discount_type" class="form-input" x-model="type">
                        <option value="">No discount</option>
                        <option value="fixed">Fixed ($)</option>
                        <option value="percentage">Percentage (%)</option>
                    </select>
                    @error('discount_type')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="discount_amount">Discount Amount</label>
                    <input type="number" step="0.01" min="0" name="discount_amount" id="discount_amount"
                        class="form-input" x-model="amount" :disabled="type === ''"
                        value="{{ old('discount_amount', $product->discount_amount ?? '') }}" placeholder="0">
                    @error('discount_amount')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="mt-2 text-sm text-gray-600 dark:text-slate-300">
                Final price after discount:
                <strong class="text-gray-900 dark:text-slate-100" x-text="'$' + final"></strong>
            </p>
        </div>

        {{-- Gallery images --}}
        <div class="col-span-2" x-data="{
            removed: [],
            previews: [],
            pick(e) { this.previews = Array.from(e.target.files).map(f => ({ name: f.name, url: URL.createObjectURL(f) })); }
        }">
            <label class="d-block mb-2" style="font-weight:700;">Product Images <span class="text-gray-400 font-normal">(multiple)</span></label>

            @if ($isEdit && $product->images->isNotEmpty())
                <div class="flex flex-wrap gap-3 mb-3">
                    @foreach ($product->images as $img)
                        <div class="relative" x-show="!removed.includes({{ $img->id }})">
                            <img src="{{ Imageurl($img->image, 'products') }}" alt="image"
                                class="w-20 h-20 object-cover rounded-lg border border-gray-200 dark:border-white/10">
                            <button type="button" @click="removed.push({{ $img->id }})"
                                class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-500 text-white text-xs flex items-center justify-center shadow"
                                title="Remove image">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
                <template x-for="id in removed" :key="id">
                    <input type="hidden" name="removed_images[]" :value="id">
                </template>
            @endif

            <input type="file" name="images[]" accept="image/*" multiple class="form-input" @change="pick($event)">
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">PNG, JPG, GIF, WEBP or SVG — up to 2MB each.</small>

            <div class="flex flex-wrap gap-3 mt-3" x-show="previews.length">
                <template x-for="p in previews" :key="p.url">
                    <img :src="p.url" :alt="p.name" class="w-20 h-20 object-cover rounded-lg border border-gray-200 dark:border-white/10">
                </template>
            </div>
            @error('images.*')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        {{-- Variants --}}
        <div class="col-span-2" x-data="{
            variants: @js($variantRows),
            add() { this.variants.push({ size_id: '', color_id: '', sku: '', stock: '0', price: '' }); },
            remove(i) { this.variants.splice(i, 1); if (this.variants.length === 0) this.add(); }
        }">
            <div class="dynamic-field-header">
                <label>Variants <span class="text-gray-400 font-normal">(size · color · SKU · stock · price)</span></label>
                <button type="button" class="dynamic-add-button" @click="add()">
                    <i class="fa-solid fa-plus"></i> Add variant
                </button>
            </div>

            <div class="d-flex flex-column gap-2 mt-2">
                <template x-for="(row, i) in variants" :key="i">
                    <div class="grid grid-cols-2 md:grid-cols-12 gap-2 items-start">
                        <select class="form-input col-span-1 md:col-span-3" x-model="row.size_id" :name="`variants[${i}][size_id]`">
                            <option value="">Size</option>
                            @foreach ($sizes as $size)
                                <option value="{{ $size->id }}">{{ $size->name }}{{ $size->code ? " ({$size->code})" : '' }}</option>
                            @endforeach
                        </select>

                        <select class="form-input col-span-1 md:col-span-3" x-model="row.color_id" :name="`variants[${i}][color_id]`">
                            <option value="">Color</option>
                            @foreach ($colors as $color)
                                <option value="{{ $color->id }}">{{ $color->name }}</option>
                            @endforeach
                        </select>

                        <input type="text" class="form-input col-span-2 md:col-span-2" x-model="row.sku"
                            :name="`variants[${i}][sku]`" placeholder="SKU">

                        <input type="number" min="0" class="form-input col-span-1 md:col-span-2" x-model="row.stock"
                            :name="`variants[${i}][stock]`" placeholder="Stock">

                        <input type="number" step="0.01" min="0" class="form-input col-span-1 md:col-span-1"
                            x-model="row.price" :name="`variants[${i}][price]`" placeholder="Price">

                        <button type="button" class="dynamic-remove-button col-span-2 md:col-span-1" @click="remove(i)" title="Remove variant">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </template>
            </div>
            <small class="text-gray-400 dark:text-slate-500 d-block mt-2">Leave variant price empty to use the product price. SKU must be unique across all products.</small>

            @foreach ($errors->keys() as $key)
                @if (str_starts_with($key, 'variants.'))
                    <p class="text-red-500 text-sm mt-1.5">{{ $errors->first($key) }}</p>
                @endif
            @endforeach
        </div>
    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.products.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
