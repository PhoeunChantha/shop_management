<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Products') }}
            </h2>
        </div>
    </x-slot>

    @php($importPreview = session('product_import_preview'))

    <div class="admin-page products-index-page" x-data="{ importOpen: false, importPreviewOpen: @js((bool) $importPreview) }">
        <div class="page-section-header products-index-hero">
            <div class="products-index-hero__copy">
                <p class="section-kicker">Product table</p>
                <h3>All Products</h3>
                <p>Manage catalog visibility, stock position, pricing, and merchandising flags from one clean workspace.</p>
            </div>
            <div class="products-index-actions">
                <a href="{{ route('admin.products.template') }}" class="ghost-button product-action-link">
                    <i class="fa-solid fa-file-arrow-down"></i><span>Template</span>
                </a>
                <a href="{{ route('admin.products.export', request()->query()) }}" class="ghost-button product-action-link">
                    <i class="fa-solid fa-file-export"></i><span>Export</span>
                </a>
                <button type="button" class="ghost-button product-action-link" @click="importOpen = true">
                    <i class="fa-solid fa-file-import"></i><span>Import</span>
                </button>
                <a href="{{ route('admin.products.create') }}" class="premium-button premium-button--dark product-create-button">
                    <i class="fa-solid fa-plus"></i>
                    <span>New Product</span>
                </a>
            </div>
        </div>

        <div class="product-metric-strip">
            <div class="product-metric">
                <span>Total catalog</span>
                <strong>{{ number_format($products->total()) }}</strong>
            </div>
            <div class="product-metric">
                <span>Categories</span>
                <strong>{{ number_format($categories->count()) }}</strong>
            </div>
            <div class="product-metric">
                <span>Brands</span>
                <strong>{{ number_format($brands->count()) }}</strong>
            </div>
            <div class="product-metric product-metric--muted">
                <span>Current view</span>
                <strong>{{ number_format($products->count()) }}</strong>
            </div>
        </div>

        {{-- Skipped rows from the last import --}}
        @if (session('import_errors'))
            <div class="premium-card p-4 mt-3" style="border-left: 3px solid var(--danger-color);">
                <p class="section-kicker mb-2" style="color: var(--danger-color);">
                    <i class="fa-solid fa-triangle-exclamation"></i> Skipped rows from last import
                </p>
                <ul class="text-sm text-gray-600 dark:text-slate-300 mb-0 ps-3" style="max-height:220px; overflow:auto; list-style:disc;">
                    @foreach (session('import_errors') as $err)
                        <li><strong>Row {{ $err['row'] }}:</strong> {{ implode(' ', $err['messages']) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Import modal --}}
        <div class="modal-backdrop-premium" x-show="importOpen" x-cloak style="display:none;"
            @keydown.escape.window="importOpen = false" @click.self="importOpen = false">
            <div class="form-modal">
                <div class="form-modal__head">
                    <div class="form-modal__icon"><i class="fa-solid fa-file-import"></i></div>
                    <div class="flex-grow-1">
                        <h3>Import Products</h3>
                        <p>Upload a filled-in template. Rows are matched by SKU (upsert).</p>
                    </div>
                    <button type="button" class="form-modal__close" @click="importOpen = false" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <form action="{{ route('admin.products.import.preview') }}" method="POST" enctype="multipart/form-data" class="form-modal__body">
                    @csrf
                    <div class="form-field">
                        <label for="import_file">Spreadsheet file <span class="text-red-500">*</span></label>
                        <input type="file" name="file" id="import_file" accept=".xlsx,.xls,.csv" class="form-input" required>
                        @error('file')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        <small class="text-gray-400 dark:text-slate-500 d-block mt-1">
                            .xlsx or .csv, up to 10MB. Need the format?
                            <a href="{{ route('admin.products.template') }}" class="text-blue-500">Download the template</a>.
                        </small>
                    </div>
                    <div class="form-modal__foot">
                        <button type="button" class="modal-cancel" @click="importOpen = false">Cancel</button>
                        <button type="submit" class="form-submit-button">
                            <i class="fa-solid fa-magnifying-glass-chart"></i>
                            <span>Preview import</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if ($importPreview)
            <div class="modal-backdrop-premium product-import-preview-backdrop" x-show="importPreviewOpen" x-cloak style="display:none;"
                @keydown.escape.window="importPreviewOpen = false" @click.self="importPreviewOpen = false">
                <div class="form-modal product-import-preview-modal">
                    <div class="form-modal__head">
                        <div class="form-modal__icon"><i class="fa-solid fa-clipboard-check"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <h3>Review Product Import</h3>
                            <p>{{ $importPreview['filename'] ?? 'Uploaded spreadsheet' }}</p>
                        </div>
                        <button type="button" class="form-modal__close" @click="importPreviewOpen = false" aria-label="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="product-import-review">
                        <div class="product-import-review__stats">
                            <span><b>{{ number_format($importPreview['valid'] ?? 0) }}</b><em>Valid rows</em></span>
                            <span class="{{ count($importPreview['errors'] ?? []) ? 'is-warning' : 'is-clean' }}">
                                <b>{{ number_format(count($importPreview['errors'] ?? [])) }}</b><em>Rows with errors</em>
                            </span>
                            <span><b>{{ number_format(count($importPreview['rows'] ?? [])) }}</b><em>Preview rows</em></span>
                        </div>

                        @if (! empty($importPreview['rows']))
                            <div class="product-import-review__table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Row</th>
                                            <th>Action</th>
                                            <th>SKU</th>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Brand</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($importPreview['rows'] as $row)
                                            <tr>
                                                <td>{{ $row['row'] }}</td>
                                                <td><span class="import-action-pill">{{ $row['action'] }}</span></td>
                                                <td>{{ $row['sku'] }}</td>
                                                <td>{{ $row['name'] }}</td>
                                                <td>{{ $row['category'] }}</td>
                                                <td>{{ $row['brand'] }}</td>
                                                <td>${{ number_format((float) $row['price'], 2) }}</td>
                                                <td>{{ ucfirst((string) $row['status']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if (! empty($importPreview['errors']))
                            <div class="product-import-review__errors">
                                <strong><i class="fa-solid fa-triangle-exclamation"></i> Fix or skip these rows</strong>
                                <ul>
                                    @foreach (array_slice($importPreview['errors'], 0, 8) as $error)
                                        <li><b>Row {{ $error['row'] }}:</b> {{ implode(' ', $error['messages']) }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="form-modal__foot">
                        <form method="POST" action="{{ route('admin.products.import.cancel') }}" class="mb-0">
                            @csrf
                            <button type="submit" class="modal-cancel">Cancel import</button>
                        </form>
                        <form method="POST" action="{{ route('admin.products.import.confirm') }}" class="mb-0">
                            @csrf
                            <button type="submit" class="form-submit-button" @disabled(($importPreview['valid'] ?? 0) < 1)>
                                <i class="fa-solid fa-file-import"></i>
                                <span>Confirm import</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Filters --}}
        <x-filter-card :action="route('admin.products.index')" class="product-filter-card">
            {{-- Search & per page live in the table toolbar; keep their values when applying filters. --}}
            <x-slot:hidden>
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            </x-slot:hidden>

            <x-select name="category_id" size="sm" :options="$categories" :value="request('category_id')"
                placeholder="All categories" searchable />

            <x-select name="brand_id" size="sm" :options="$brands" :value="request('brand_id')"
                placeholder="All brands" searchable />

            <x-select name="status" size="sm" :value="request('status')" placeholder="Any status"
                :options="['draft' => 'Draft', 'active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived']" />

            <x-select name="stock" size="sm" :value="request('stock')" placeholder="Any stock"
                :options="['in_stock' => 'In stock', 'low_stock' => 'Low stock', 'out_of_stock' => 'Out of stock']" />

            <x-select name="flag" size="sm" :value="request('flag')" placeholder="Any flag"
                :options="['featured' => 'Featured', 'new' => 'New Arrival', 'best_seller' => 'Best Seller', 'on_sale' => 'On Sale']" />
        </x-filter-card>

        <x-admin.table-card class="mt-3 product-table-card" bulk>
            <x-slot:bulkBar>
                <x-bulk-bar :destroy="route('admin.products.bulk-destroy')" noun="product">
                    <x-slot:actions>
                        <form method="POST" action="{{ route('admin.products.bulk-update') }}" class="bulk-bar__form product-bulk-form">
                            @csrf
                            @method('PATCH')
                            <template x-for="id in selected" :key="'bulk-status-' + id"><input type="hidden" name="ids[]" :value="id"></template>
                            <input type="hidden" name="operation" value="status">
                            <select name="status" class="bulk-select" aria-label="Bulk product status">
                                <option value="active">Active</option>
                                <option value="draft">Draft</option>
                                <option value="inactive">Inactive</option>
                                <option value="archived">Archived</option>
                            </select>
                            <button type="submit" class="bulk-btn"><i class="fa-solid fa-toggle-on"></i> Status</button>
                        </form>

                        <form method="POST" action="{{ route('admin.products.bulk-update') }}" class="bulk-bar__form product-bulk-form">
                            @csrf
                            @method('PATCH')
                            <template x-for="id in selected" :key="'bulk-category-' + id"><input type="hidden" name="ids[]" :value="id"></template>
                            <input type="hidden" name="operation" value="category">
                            <select name="category_id" class="bulk-select" aria-label="Bulk product category" required>
                                <option value="">Category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bulk-btn"><i class="fa-solid fa-layer-group"></i> Move</button>
                        </form>

                        <form method="POST" action="{{ route('admin.products.bulk-update') }}" class="bulk-bar__form product-bulk-form">
                            @csrf
                            @method('PATCH')
                            <template x-for="id in selected" :key="'bulk-brand-' + id"><input type="hidden" name="ids[]" :value="id"></template>
                            <input type="hidden" name="operation" value="brand">
                            <select name="brand_id" class="bulk-select" aria-label="Bulk product brand" required>
                                <option value="">Brand</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bulk-btn"><i class="fa-solid fa-copyright"></i> Brand</button>
                        </form>

                        <form method="POST" action="{{ route('admin.products.bulk-update') }}" class="bulk-bar__form product-bulk-form">
                            @csrf
                            @method('PATCH')
                            <template x-for="id in selected" :key="'bulk-flag-' + id"><input type="hidden" name="ids[]" :value="id"></template>
                            <input type="hidden" name="operation" value="flag">
                            <select name="flag" class="bulk-select" aria-label="Bulk product flag">
                                <option value="is_featured">Featured</option>
                                <option value="is_new">New</option>
                                <option value="is_best_seller">Best seller</option>
                                <option value="is_on_sale">On sale</option>
                            </select>
                            <select name="flag_value" class="bulk-select bulk-select--mini" aria-label="Bulk product flag value">
                                <option value="1">On</option>
                                <option value="0">Off</option>
                            </select>
                            <button type="submit" class="bulk-btn"><i class="fa-solid fa-tags"></i> Flag</button>
                        </form>

                        <form method="POST" action="{{ route('admin.products.bulk-export') }}" class="bulk-bar__form">
                            @csrf
                            <template x-for="id in selected" :key="'bulk-export-' + id"><input type="hidden" name="ids[]" :value="id"></template>
                            <button type="submit" class="bulk-btn"><i class="fa-solid fa-file-export"></i> Export</button>
                        </form>
                    </x-slot:actions>
                </x-bulk-bar>
            </x-slot:bulkBar>

            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" />
                    </x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="Search products by name, SKU or barcode..." />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

                <table class="premium-table product-management-table">
                    <thead>
                        <tr>
                            <th class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" @change="toggleAll($event)"
                                    :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="Select all">
                            </th>
                            <th class="text-center" style="width:56px;">#</th>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Flags</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr class="product-row">
                                <td class="bulk-check-col">
                                    <input type="checkbox" class="bulk-check" data-row-check value="{{ $product->id }}"
                                        x-model="selected" aria-label="Select row">
                                </td>
                                <td class="text-center text-sm text-gray-500 dark:text-slate-400">{{ ($products->firstItem() ?? 0) + $loop->index }}</td>
                                <td>
                                    @if ($product->thumbnail)
                                        <img src="{{ Imageurl($product->thumbnail, 'products') }}" alt="{{ $product->name }}"
                                            class="product-thumb">
                                    @else
                                        <span class="product-thumb product-thumb--empty"><i class="fa-regular fa-image"></i></span>
                                    @endif
                                </td>
                                <td>
                                    <div class="product-cell-main">
                                        <strong>{{ $product->name }}</strong>
                                        <div>{{ $product->slug }}</div>
                                    </div>
                                </td>
                                <td><span class="text-sm text-gray-600 dark:text-slate-300">{{ $product->category->name ?? '—' }}</span></td>
                                <td><span class="text-sm text-gray-600 dark:text-slate-300">{{ $product->brand->name ?? '—' }}</span></td>
                                <td>
                                    @if ($product->has_discount)
                                        <strong class="product-price">${{ number_format($product->final_price, 2) }}</strong>
                                        <span class="product-price-was">${{ number_format($product->price, 2) }}</span>
                                    @else
                                        <strong class="product-price">${{ number_format($product->price, 2) }}</strong>
                                    @endif
                                </td>
                                <td><span class="product-stock">{{ $product->total_stock }}</span></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @if ($product->is_featured)<span class="pill-badge pill-featured">Featured</span>@endif
                                        @if ($product->is_new)<span class="pill-badge pill-new">New</span>@endif
                                        @if ($product->is_best_seller)<span class="pill-badge pill-best">Best</span>@endif
                                        @if ($product->is_on_sale)<span class="pill-badge pill-sale">Sale</span>@endif
                                    </div>
                                </td>
                                <td>
                                    @php($map = ['active' => 'st-active', 'draft' => 'st-draft', 'inactive' => 'st-inactive', 'archived' => 'st-archived'])
                                    <span class="status-chip {{ $map[$product->status] ?? 'st-draft' }}">{{ ucfirst($product->status) }}</span>
                                </td>
                                <td><span class="text-xs text-gray-500 dark:text-slate-400">{{ $product->created_at?->format('M d, Y') }}</span></td>
                                <td>
                                    <div class="action-group">
                                        <x-table-actions>
                                            <a href="{{ route('admin.products.show', $product->id) }}" class="table-actions__item" role="menuitem">
                                                <i class="fa-solid fa-eye"></i><span>View</span>
                                            </a>
                                            <a href="{{ route('admin.products.edit', $product->id) }}" class="table-actions__item" role="menuitem">
                                                <i class="fa-solid fa-pen"></i><span>Edit</span>
                                            </a>
                                            <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                                data-delete-modal-target="deleteProductModal"
                                                data-delete-action="{{ route('admin.products.destroy', $product->id) }}"
                                                data-delete-name="{{ $product->name }}">
                                                <i class="fa-solid fa-trash"></i><span>Delete</span>
                                            </button>
                                        </x-table-actions>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12">
                                    <x-admin.empty-state icon="fa-solid fa-box-open" title="No products found"
                                        message="Create your first product or adjust the filters." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            <x-slot:footer>
                <x-table-footer :paginator="$products" label="products" />
            </x-slot:footer>
        </x-admin.table-card>

        <x-delete-confirm-modal id="deleteProductModal" title="Delete this product?"
            message-after="and all its images, variants and specifications. This cannot be undone." />
    </div>
</x-app-layout>
