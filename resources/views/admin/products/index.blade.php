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

            <select name="category_id" class="form-input">
                    <option value="">All categories</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
                <select name="brand_id" class="form-input">
                    <option value="">All brands</option>
                    @foreach ($brands as $b)
                        <option value="{{ $b->id }}" @selected(request('brand_id') == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-input">
                    <option value="">Any status</option>
                    @foreach (['draft', 'active', 'inactive', 'archived'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <select name="stock" class="form-input">
                    <option value="">Any stock</option>
                    <option value="in_stock" @selected(request('stock') === 'in_stock')>In stock</option>
                    <option value="low_stock" @selected(request('stock') === 'low_stock')>Low stock</option>
                    <option value="out_of_stock" @selected(request('stock') === 'out_of_stock')>Out of stock</option>
                </select>
                <select name="flag" class="form-input">
                    <option value="">Any flag</option>
                    <option value="featured" @selected(request('flag') === 'featured')>Featured</option>
                    <option value="new" @selected(request('flag') === 'new')>New Arrival</option>
                    <option value="best_seller" @selected(request('flag') === 'best_seller')>Best Seller</option>
                    <option value="on_sale" @selected(request('flag') === 'on_sale')>On Sale</option>
                </select>
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
