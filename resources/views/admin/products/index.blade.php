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
        <form method="GET" action="{{ route('admin.products.index') }}" class="premium-card filter-card product-filters">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <label class="search-control lg:col-span-2">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, SKU or barcode..." autocomplete="off">
                </label>
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
                <div class="d-flex gap-2">
                    <input type="number" step="0.01" name="min_price" value="{{ request('min_price') }}" class="form-input" placeholder="Min $">
                    <input type="number" step="0.01" name="max_price" value="{{ request('max_price') }}" class="form-input" placeholder="Max $">
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3">
                <button type="submit" class="filter-button"><i class="fa-solid fa-sliders"></i> Apply Filters</button>
                <a href="{{ route('admin.products.index') }}" class="ghost-button"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                <label class="per-page-control ms-auto">
                    <span>Show</span>
                    <select name="per_page" onchange="this.form.submit()">
                        @foreach ([5, 10, 25, 50] as $size)
                            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </form>

        <section class="premium-card mt-3">
            <div class="table-toolbar">
                <div class="result-badge">
                    <i class="fa-solid fa-box-open"></i>
                    <span>{{ $products->total() }} result{{ $products->total() === 1 ? '' : 's' }}</span>
                </div>
            </div>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
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
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        @php($thumb = $product->thumbnail_url)
                                        @if ($thumb)
                                            <img src="{{ $thumb }}" alt="{{ $product->name }}" class="w-11 h-11 object-cover rounded-lg border dark:border-white/10" style="width:44px;height:44px;">
                                        @else
                                            <span class="d-inline-flex align-items-center justify-content-center rounded-lg bg-gray-100 text-gray-300 dark:bg-white/10" style="width:44px;height:44px;"><i class="fa-regular fa-image"></i></span>
                                        @endif
                                        <div>
                                            <strong class="text-gray-900 dark:text-slate-100">{{ $product->name }}</strong>
                                            <div class="text-xs text-gray-400 dark:text-slate-500 font-mono">{{ $product->slug }}</div>
                                        </div>
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
                                        <a href="{{ route('admin.products.show', $product->id) }}" class="table-action table-action--view"><i class="fa-solid fa-eye"></i><span>View</span></a>
                                        <a href="{{ route('admin.products.edit', $product->id) }}" class="table-action table-action--edit"><i class="fa-solid fa-pen"></i><span>Edit</span></a>
                                        <button type="button" class="table-action table-action--delete"
                                            data-delete-modal-target="deleteProductModal"
                                            data-delete-action="{{ route('admin.products.destroy', $product->id) }}"
                                            data-delete-name="{{ $product->name }}">
                                            <i class="fa-solid fa-trash"></i><span>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
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
