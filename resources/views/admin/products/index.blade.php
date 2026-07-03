<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Products') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Product table</p>
                <h3>All Products</h3>
            </div>
            <a href="{{ route('admin.products.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>New Product</span>
            </a>
        </div>

        {{-- Filters --}}
        <x-filter-card :action="route('admin.products.index')">
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

        <section class="premium-card mt-3">
            <x-table-toolbar>
                <x-slot:left>
                    <div class="result-badge">
                        <i class="fa-solid fa-box-open"></i>
                        <span>{{ $products->total() }} result{{ $products->total() === 1 ? '' : 's' }}</span>
                    </div>
                    <x-per-page-selector :current="$perPage" />
                </x-slot:left>
                <x-slot:right>
                    <x-search-input name="search" placeholder="Search name, SKU or barcode..." />
                </x-slot:right>
            </x-table-toolbar>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
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
                            <tr>
                                <td class="text-center text-sm text-gray-500 dark:text-slate-400">{{ ($products->firstItem() ?? 0) + $loop->index }}</td>
                                <td>
                                    @if ($product->thumbnail)
                                        <img src="{{ Imageurl($product->thumbnail, 'products') }}" alt="{{ $product->name }}"
                                            class="w-11 h-11 object-cover rounded-lg border dark:border-white/10" style="width:44px;height:44px;">
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-lg bg-gray-100 text-gray-300 dark:bg-white/10" style="width:44px;height:44px;"><i class="fa-regular fa-image"></i></span>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        <strong class="text-gray-900 dark:text-slate-100">{{ $product->name }}</strong>
                                        <div class="text-xs text-gray-400 dark:text-slate-500 font-mono">{{ $product->slug }}</div>
                                    </div>
                                </td>
                                <td><span class="text-sm text-gray-600 dark:text-slate-300">{{ $product->category->name ?? '—' }}</span></td>
                                <td><span class="text-sm text-gray-600 dark:text-slate-300">{{ $product->brand->name ?? '—' }}</span></td>
                                <td>
                                    @if ($product->has_discount)
                                        <strong class="text-gray-900 dark:text-slate-100">${{ number_format($product->final_price, 2) }}</strong>
                                        <span class="text-xs text-gray-400 line-through ml-1">${{ number_format($product->price, 2) }}</span>
                                    @else
                                        <strong class="text-gray-900 dark:text-slate-100">${{ number_format($product->price, 2) }}</strong>
                                    @endif
                                </td>
                                <td><span class="text-sm text-gray-600 dark:text-slate-300">{{ (int) $product->variants_sum_stock }}</span></td>
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
                                <td colspan="11">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-box-open"></i>
                                        <strong>No products found</strong>
                                        <span>Create your first product or adjust the filters.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$products" label="products" />
        </section>

        <x-delete-confirm-modal id="deleteProductModal" title="Delete this product?"
            message-after="and all its images, variants and specifications. This cannot be undone." />
    </div>
</x-app-layout>
