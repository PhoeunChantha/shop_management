<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Product Detail') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Product detail</p>
                <h3>{{ $product->name }}</h3>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.products.edit', $product->id) }}" class="premium-button premium-button--dark">
                    <i class="fa-solid fa-pen"></i><span>Edit</span>
                </a>
                <a href="{{ route('admin.products.index') }}" class="ghost-button ghost-button--panel">
                    <i class="fa-solid fa-arrow-left"></i><span>Back</span>
                </a>
            </div>
        </div>

        <x-message />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- Gallery --}}
            <section class="premium-card p-4 lg:col-span-1">
                <p class="section-kicker mb-2">Gallery</p>
                @php($cover = $product->thumbnail_url)
                @if ($cover)
                    <img src="{{ $cover }}" alt="{{ $product->name }}" class="w-full h-64 object-cover rounded-xl border border-gray-200 dark:border-white/10">
                @else
                    <div class="empty-state"><i class="fa-regular fa-image"></i><strong>No images</strong></div>
                @endif
                @if ($product->images->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mt-3">
                        @foreach ($product->images as $img)
                            <img src="{{ Imageurl($img->image, 'products') }}" alt="image"
                                class="w-16 h-16 object-cover rounded-lg border {{ $img->is_primary ? 'border-amber-400' : 'border-gray-200 dark:border-white/10' }}">
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Info --}}
            <section class="premium-card p-4 lg:col-span-2">
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

                <div class="d-flex align-items-baseline gap-2 mb-3">
                    <span class="text-2xl font-bold text-gray-900 dark:text-slate-100">${{ number_format($product->final_price, 2) }}</span>
                    @if ($product->has_discount)
                        <span class="text-gray-400 line-through">${{ number_format($product->price, 2) }}</span>
                        <span class="pill-badge pill-sale">
                            {{ $product->discount_type === 'percentage' ? rtrim(rtrim(number_format($product->discount_amount, 2), '0'), '.') . '% off' : '$' . number_format($product->discount_amount, 2) . ' off' }}
                        </span>
                    @endif
                </div>

                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-gray-500 dark:text-slate-400">Slug</dt>
                    <dd class="font-mono text-gray-800 dark:text-slate-200">{{ $product->slug }}</dd>
                    <dt class="text-gray-500 dark:text-slate-400">Category</dt>
                    <dd class="text-gray-800 dark:text-slate-200">{{ $product->category->name ?? '—' }}{{ $product->subCategory ? ' / ' . $product->subCategory->name : '' }}</dd>
                    <dt class="text-gray-500 dark:text-slate-400">Brand</dt>
                    <dd class="text-gray-800 dark:text-slate-200">{{ $product->brand->name ?? '—' }}</dd>
                    <dt class="text-gray-500 dark:text-slate-400">Cost Price</dt>
                    <dd class="text-gray-800 dark:text-slate-200">{{ $product->cost_price !== null ? '$' . number_format($product->cost_price, 2) : '—' }}</dd>
                    <dt class="text-gray-500 dark:text-slate-400">Weight</dt>
                    <dd class="text-gray-800 dark:text-slate-200">{{ $product->weight !== null ? $product->weight . ' kg' : '—' }}</dd>
                    <dt class="text-gray-500 dark:text-slate-400">Total Stock</dt>
                    <dd class="text-gray-800 dark:text-slate-200">{{ $product->variants->sum('stock') }}</dd>
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

        {{-- Variants --}}
        <section class="premium-card mt-4">
            <div class="table-titlebar">
                <div><h3>Variants</h3><p>{{ $product->variants->count() }} variant{{ $product->variants->count() === 1 ? '' : 's' }}.</p></div>
            </div>
            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Size</th><th>Color</th><th>SKU</th><th>Barcode</th><th>Stock</th><th>Price</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($product->variants as $variant)
                            <tr>
                                <td><strong>{{ $variant->size->name ?? '—' }}</strong></td>
                                <td>
                                    <span class="d-inline-flex align-items-center gap-2">
                                        @if ($variant->color?->hex_code)
                                            <span class="d-inline-block rounded-circle border" style="width:16px;height:16px;background: {{ $variant->color->hex_code }};"></span>
                                        @endif
                                        {{ $variant->color->name ?? '—' }}
                                    </span>
                                </td>
                                <td><span class="font-mono text-sm">{{ $variant->sku }}</span></td>
                                <td><span class="font-mono text-sm text-gray-500">{{ $variant->barcode ?: '—' }}</span></td>
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
