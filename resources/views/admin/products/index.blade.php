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

        <section class="premium-card">
            <form method="GET" action="{{ route('admin.products.index') }}" class="table-toolbar">
                <div class="table-toolbar__left">
                    <div class="result-badge">
                        <i class="fa-solid fa-box-open"></i>
                        <span>{{ $products->total() }} result{{ $products->total() === 1 ? '' : 's' }}</span>
                    </div>

                    <label class="per-page-control">
                        <span>Show</span>
                        <select name="per_page" onchange="this.form.submit()">
                            @foreach ([5, 10, 25, 50] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                        <span>per page</span>
                    </label>

                    <label class="per-page-control">
                        <span>Status</span>
                        <select name="status" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </label>
                </div>

                <label class="search-control">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="search" value="{{ request('search') }}"
                        placeholder="Search products..." autocomplete="off" data-auto-search>
                </label>
            </form>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Variants</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td><span class="muted-id">#{{ $product->id }}</span></td>
                                <td>
                                    @php($thumb = $product->images->first())
                                    @if ($thumb)
                                        <img src="{{ Imageurl($thumb->image, 'products') }}" alt="{{ $product->name }}"
                                            class="w-10 h-10 object-cover rounded border dark:border-white/10">
                                    @else
                                        <span class="text-gray-300 dark:text-slate-600 text-xs">No Image</span>
                                    @endif
                                </td>
                                <td>
                                    <strong class="text-gray-900 dark:text-slate-100">{{ $product->name }}</strong>
                                    <div class="text-xs text-gray-400 dark:text-slate-500 font-mono">{{ $product->slug }}</div>
                                </td>
                                <td>
                                    <span class="text-sm text-gray-600 dark:text-slate-300">{{ $product->category->name ?? '—' }}</span>
                                </td>
                                <td>
                                    @if ($product->has_discount)
                                        <strong class="text-gray-900 dark:text-slate-100">${{ number_format($product->final_price, 2) }}</strong>
                                        <span class="text-xs text-gray-400 line-through ml-1">${{ number_format($product->price, 2) }}</span>
                                    @else
                                        <strong class="text-gray-900 dark:text-slate-100">${{ number_format($product->price, 2) }}</strong>
                                    @endif
                                </td>
                                <td>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded border border-gray-200 dark:bg-white/10 dark:text-slate-200 dark:border-white/10">
                                        {{ $product->variants_count }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-sm text-gray-600 dark:text-slate-300">{{ (int) $product->variants_sum_stock }}</span>
                                </td>
                                <td>
                                    @if ($product->status === 'active')
                                        <span class="text-green-600 bg-green-50 px-2 py-1 rounded text-xs font-medium border border-green-200 dark:text-emerald-300 dark:bg-emerald-500/10 dark:border-emerald-500/20">Active</span>
                                    @else
                                        <span class="text-red-600 bg-red-50 px-2 py-1 rounded text-xs font-medium border border-red-200 dark:text-red-300 dark:bg-red-500/10 dark:border-red-500/20">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="{{ route('admin.products.show', $product->id) }}"
                                            class="table-action table-action--view">
                                            <i class="fa-solid fa-eye"></i>
                                            <span>View</span>
                                        </a>
                                        <a href="{{ route('admin.products.edit', $product->id) }}"
                                            class="table-action table-action--edit">
                                            <i class="fa-solid fa-pen"></i>
                                            <span>Edit</span>
                                        </a>
                                        <button type="button" class="table-action table-action--delete"
                                            data-delete-modal-target="deleteProductModal"
                                            data-delete-action="{{ route('admin.products.destroy', $product->id) }}"
                                            data-delete-name="{{ $product->name }}">
                                            <i class="fa-solid fa-trash"></i>
                                            <span>Delete</span>
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
                                        <span>Create your first product or adjust the current search.</span>
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
            message-after="and all its images and variants. This cannot be undone." />
    </div>
</x-app-layout>
