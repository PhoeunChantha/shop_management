<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Product Detail') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page product-show-page">
        <div class="page-section-header product-show-hero">
            <div class="product-show-hero__copy">
                <p class="section-kicker">Product detail</p>
                <h3>{{ $product->name }}</h3>
                <div class="product-show-hero__meta">
                    @php($map = ['active' => 'st-active', 'draft' => 'st-draft', 'inactive' => 'st-inactive', 'archived' => 'st-archived'])
                    <span class="status-chip {{ $map[$product->status] ?? 'st-draft' }}">{{ ucfirst($product->status) }}</span>
                    <span><i class="fa-solid fa-layer-group"></i>{{ $product->category->name ?? 'Uncategorized' }}</span>
                    <span><i class="fa-solid fa-cubes-stacked"></i>{{ $product->isSingle() ? $product->stock : $product->variants->sum('stock') }} in stock</span>
                </div>
            </div>
            <div class="product-show-hero__price">
                <span>Storefront price</span>
                <strong>${{ number_format($product->final_price, 2) }}</strong>
            </div>
            <div class="product-show-hero__actions">
                <a href="{{ route('admin.products.edit', $product->id) }}" class="premium-button premium-button--dark">
                    <i class="fa-solid fa-pen"></i><span>Edit</span>
                </a>
                <a href="{{ route('admin.products.index') }}" class="ghost-button ghost-button--panel">
                    <i class="fa-solid fa-arrow-left"></i><span>Back</span>
                </a>
            </div>
        </div>

        <x-message />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 product-show-layout">

            {{-- Gallery --}}
            <section class="premium-card product-show-gallery lg:col-span-1">
                <p class="section-kicker mb-2">Gallery</p>
                @php($cover = $product->thumbnail_url)
                @if ($cover)
                    <img src="{{ $cover }}" alt="{{ $product->name }}" class="product-show-gallery__cover">
                @else
                    <div class="empty-state"><i class="fa-regular fa-image"></i><strong>No images</strong></div>
                @endif
                @if ($product->images->isNotEmpty())
                    <div class="product-show-gallery__thumbs">
                        @foreach ($product->images as $img)
                            <img src="{{ Imageurl($img->image, 'products') }}" alt="image"
                                class="{{ $img->is_primary ? 'is-primary' : '' }}">
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Info --}}
            <section class="premium-card product-show-overview lg:col-span-2">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <p class="section-kicker mb-0">Overview</p>
                    <div class="d-flex flex-wrap gap-1">
                        @if ($product->is_featured)<span class="pill-badge pill-featured">Featured</span>@endif
                        @if ($product->is_new)<span class="pill-badge pill-new">New</span>@endif
                        @if ($product->is_best_seller)<span class="pill-badge pill-best">Best Seller</span>@endif
                        @if ($product->is_on_sale)<span class="pill-badge pill-sale">On Sale</span>@endif
                        @php($map = ['active' => 'st-active', 'draft' => 'st-draft', 'inactive' => 'st-inactive', 'archived' => 'st-archived'])
                        <span class="status-chip {{ $map[$product->status] ?? 'st-draft' }}">{{ ucfirst($product->status) }}</span>
                    </div>
                </div>

                <div class="product-show-price-row">
                    <span>${{ number_format($product->final_price, 2) }}</span>
                    @if ($product->has_discount)
                        <del>${{ number_format($product->price, 2) }}</del>
                        <span class="pill-badge pill-sale">
                            {{ $product->discount_type === 'percentage' ? rtrim(rtrim(number_format($product->discount_amount, 2), '0'), '.') . '% off' : '$' . number_format($product->discount_amount, 2) . ' off' }}
                        </span>
                    @endif
                </div>

                <dl class="product-show-facts">
                    <dt class="text-gray-500 dark:text-slate-400">Slug</dt>
                    <dd class="font-mono text-gray-800 dark:text-slate-200">{{ $product->slug }}</dd>
                    <dt class="text-gray-500 dark:text-slate-400">Category</dt>
                    <dd class="text-gray-800 dark:text-slate-200">{{ $product->category->name ?? 'N/A' }}{{ $product->subCategory ? ' / ' . $product->subCategory->name : '' }}</dd>
                    <dt class="text-gray-500 dark:text-slate-400">Brand</dt>
                    <dd class="text-gray-800 dark:text-slate-200">{{ $product->brand->name ?? 'N/A' }}</dd>
                    <dt class="text-gray-500 dark:text-slate-400">Cost Price</dt>
                    <dd class="text-gray-800 dark:text-slate-200">{{ $product->cost_price !== null ? '$' . number_format($product->cost_price, 2) : 'N/A' }}</dd>
                    <dt class="text-gray-500 dark:text-slate-400">Weight</dt>
                    <dd class="text-gray-800 dark:text-slate-200">{{ $product->weight !== null ? $product->weight . ' kg' : 'N/A' }}</dd>
                    <dt class="text-gray-500 dark:text-slate-400">Total Stock</dt>
                    <dd class="text-gray-800 dark:text-slate-200">{{ $product->isSingle() ? $product->stock : $product->variants->sum('stock') }}</dd>
                </dl>

                @if ($product->tags->isNotEmpty())
                    <div class="mt-3 d-flex flex-wrap gap-1">
                        @foreach ($product->tags as $tag)
                            <span class="tag-chip is-static">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif

                @if ($product->short_description)
                    <p class="section-kicker mt-4 mb-1">Short Description</p>
                    <p class="text-sm text-gray-600 dark:text-slate-300">{{ $product->short_description }}</p>
                @endif
                @if ($product->description)
                    <p class="section-kicker mt-3 mb-1">Description</p>
                    <p class="text-sm text-gray-600 dark:text-slate-300">{{ $product->description }}</p>
                @endif
            </section>
        </div>

        {{-- Variants / stock --}}
        <section class="premium-card mt-4">
            @if ($product->isSingle())
                <div class="table-titlebar">
                    <div><h3>Stock</h3><p>Single product - one SKU.</p></div>
                </div>
                <div class="premium-table-wrap">
                    <table class="premium-table">
                        <thead><tr><th>SKU</th><th>Stock</th><th>Low Stock Alert</th></tr></thead>
                        <tbody>
                            <tr>
                                <td><span class="font-mono text-sm">{{ $product->sku ?: 'N/A' }}</span></td>
                                <td>{{ $product->stock }}</td>
                                <td>{{ $product->low_stock_alert }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                <div class="table-titlebar">
                    <div><h3>Variants</h3><p>{{ $product->variants->count() }} variant{{ $product->variants->count() === 1 ? '' : 's' }}.</p></div>
                </div>
                <div class="premium-table-wrap">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Image</th><th>Variant</th><th>SKU</th><th>Barcode</th><th>Stock</th><th>Price</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($product->variants as $variant)
                                <tr>
                                    <td>
                                        @if ($variant->image_url)
                                            <img src="{{ $variant->image_url }}" alt="" class="w-10 h-10 object-cover rounded border dark:border-white/10">
                                        @else
                                            <span class="d-inline-flex align-items-center justify-content-center rounded bg-gray-100 text-gray-300 dark:bg-white/10" style="width:40px;height:40px;"><i class="fa-regular fa-image"></i></span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse ($variant->values as $value)
                                                <span class="attr-value-pill">
                                                    @if ($value->color_hex)
                                                        <span class="attr-swatch" style="background: {{ $value->color_hex }};"></span>
                                                    @endif
                                                    {{ $value->value }}
                                                </span>
                                            @empty
                                                <span class="text-gray-400">N/A</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td><span class="font-mono text-sm">{{ $variant->sku }}</span></td>
                                    <td><span class="font-mono text-sm text-gray-500">{{ $variant->barcode ?: 'N/A' }}</span></td>
                                    <td>
                                        {{ $variant->stock }}
                                        @if ($variant->is_low_stock)<span class="pill-badge pill-sale ms-1">Low</span>@endif
                                    </td>
                                    <td>{{ $variant->price !== null ? '$' . number_format($variant->price, 2) : '$' . number_format($product->price, 2) }}</td>
                                    <td><span class="status-chip {{ $variant->status ? 'st-active' : 'st-inactive' }}">{{ $variant->status ? 'Active' : 'Inactive' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-layer-group"></i><strong>No variants</strong></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Specifications --}}
        @if ($product->specifications->isNotEmpty())
            <section class="premium-card mt-4">
                <div class="table-titlebar"><div><h3>Specifications</h3></div></div>
                <div class="premium-table-wrap">
                    <table class="premium-table">
                        <tbody>
                            @foreach ($product->specifications as $spec)
                                <tr>
                                    <td style="width:240px;"><strong>{{ $spec->name }}</strong></td>
                                    <td>{{ $spec->value }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($product->seo_title || $product->seo_description)
            <section class="premium-card p-4 mt-4">
                <p class="section-kicker mb-2">SEO</p>
                @if ($product->seo_title)<p class="text-sm"><strong>Title:</strong> {{ $product->seo_title }}</p>@endif
                @if ($product->seo_description)<p class="text-sm text-gray-600 dark:text-slate-300"><strong>Description:</strong> {{ $product->seo_description }}</p>@endif
            </section>
        @endif
    </div>
</x-app-layout>
