@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $c = $collection ?? null;
    $selectedIds = array_map('intval', $selected ?? []);
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="form-field col-span-2">
            <x-image-upload name="image" label="Cover image" folder="collections"
                :value="$c && $c->image ? 'uploads/collections/' . $c->image : null"
                help="Optional — JPG, PNG or WebP, up to 4MB" />
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="name">Name <span class="text-red-500">*</span></label>
            <input value="{{ old('name', $c->name ?? '') }}" type="text" name="name" id="name"
                class="form-input" placeholder="e.g. Summer Essentials" required>
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">The URL slug is generated automatically.</small>
            @error('name')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="sort_order">Sort order</label>
            <input value="{{ old('sort_order', $c->sort_order ?? 0) }}" type="number" min="0" name="sort_order"
                id="sort_order" class="form-input" placeholder="0">
            <small class="text-gray-400 dark:text-slate-500 d-block mt-1">Lower numbers show first.</small>
            @error('sort_order')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-input" rows="2"
                placeholder="Short description shown on the collection card">{{ old('description', $c->description ?? '') }}</textarea>
            @error('description')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $c->status ?? 1) == 1 ? 'selected' : '' }}>Enabled</option>
                <option value="0" {{ old('status', $c->status ?? 1) == 0 ? 'selected' : '' }}>Disabled</option>
            </select>
            @error('status')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        {{-- Product picker --}}
        <div class="form-field col-span-2"
            x-data="collectionProductPicker(@js($products), @js($selectedIds))" @click.outside="open = false">
            <label>Products in this collection</label>

            <div class="picker__control" @click="open = true">
                <template x-for="p in chosen" :key="p.id">
                    <span class="picker__chip">
                        <template x-if="p.thumb"><img :src="p.thumb" alt=""></template>
                        <span x-text="p.name"></span>
                        <button type="button" @click.stop="remove(p.id)" aria-label="Remove"><i class="fa-solid fa-xmark"></i></button>
                    </span>
                </template>
                <input type="text" class="picker__search" x-model="query" @focus="open = true"
                    placeholder="Search products to add…">
            </div>

            <div class="picker__menu" x-show="open && results.length" x-cloak>
                <template x-for="p in results" :key="p.id">
                    <button type="button" class="picker__opt" @click="add(p.id)">
                        <template x-if="p.thumb"><img :src="p.thumb" alt=""></template>
                        <template x-if="!p.thumb"><span class="picker__opt-ph"><i class="fa-solid fa-box"></i></span></template>
                        <span x-text="p.name"></span>
                    </button>
                </template>
            </div>

            <template x-for="id in selected" :key="id"><input type="hidden" name="products[]" :value="id"></template>

            <p class="form-help mt-2"><span x-text="selected.length"></span> product(s) selected.</p>
            @error('products')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="form-panel-footer mt-6">
        <a href="{{ route('admin.collections.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>

@once
    @push('js')
        <script>
            window.collectionProductPicker = (all, selected) => ({
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
                        .slice(0, 8);
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
