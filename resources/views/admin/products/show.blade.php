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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- Gallery --}}
            <section class="premium-card p-4 lg:col-span-1">
                <p class="section-kicker mb-2">Gallery</p>
                @if ($product->images->isNotEmpty())
                    @php($cover = $product->images->first())
                    <img src="{{ Imageurl($cover->image, 'products') }}" alt="{{ $product->name }}"
                        class="w-full h-64 object-cover rounded-xl border border-gray-200 dark:border-white/10">
                    @if ($product->images->count() > 1)
                        <div class="flex flex-wrap gap-2 mt-3">
                            @foreach ($product->images as $img)
                                <img src="{{ Imageurl($img->image, 'products') }}" alt="image"
                                    class="w-16 h-16 object-cover rounded-lg border border-gray-200 dark:border-white/10">
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="empty-state"><i class="fa-regular fa-image"></i><strong>No images</strong></div>
                @endif
            </section>

            {{-- Info --}}
            <section class="premium-card p-4 lg:col-span-2">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <p class="section-kicker mb-0">Overview</p>
                    @if ($product->status === 'active')
                        <span class="text-green-600 bg-green-50 px-2 py-1 rounded text-xs font-medium border border-green-200 dark:text-emerald-300 dark:bg-emerald-500/10 dark:border-emerald-500/20">Active</span>
                    @else
                        <span class="text-red-600 bg-red-50 px-2 py-1 rounded text-xs font-medium border border-red-200 dark:text-red-300 dark:bg-red-500/10 dark:border-red-500/20">Inactive</span>
                    @endif
                </div>

                <div class="d-flex align-items-baseline gap-2 mb-3">
                    <span class="text-2xl font-bold text-gray-900 dark:text-slate-100">${{ number_format($product->final_price, 2) }}</span>
                    @if ($product->has_discount)
                        <span class="text-gray-400 line-through">${{ number_format($product->price, 2) }}</span>
                        <span class="text-xs font-semibold px-2 py-1 rounded bg-amber-50 text-amber-700 border border-amber-200">
                            {{ $product->discount_type === 'percentage' ? rtrim(rtrim(number_format($product->discount_amount, 2), '0'), '.') . '% off' : '$' . number_format($product->discount_amount, 2) . ' off' }}
                        </span>
                    @endif
                </div>

                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-gray-500 dark:text-slate-400">Slug</dt>
                    <dd class="font-mono text-gray-800 dark:text-slate-200">{{ $product->slug }}</dd>

                    <dt class="text-gray-500 dark:text-slate-400">Category</dt>
                    <dd class="text-gray-800 dark:text-slate-200">{{ $product->category->name ?? '—' }}</dd>

                    <dt class="text-gray-500 dark:text-slate-400">Sub Category</dt>
                    <dd class="text-gray-800 dark:text-slate-200">{{ $product->subCategory->name ?? '—' }}</dd>

                    <dt class="text-gray-500 dark:text-slate-400">Total Stock</dt>
                    <dd class="text-gray-800 dark:text-slate-200">{{ $product->variants->sum('stock') }}</dd>
                </dl>

                @if ($product->description)
                    <p class="section-kicker mt-4 mb-1">Description</p>
                    <p class="text-sm text-gray-600 dark:text-slate-300">{{ $product->description }}</p>
                @endif
            </section>
        </div>

        {{-- Variants --}}
        <section class="premium-card mt-4">
            <div class="table-titlebar">
                <div>
                    <h3>Variants</h3>
                    <p>{{ $product->variants->count() }} variant{{ $product->variants->count() === 1 ? '' : 's' }} · size, color, SKU, stock and price.</p>
                </div>
            </div>
            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Size</th>
                            <th>Color</th>
                            <th>SKU</th>
                            <th>Stock</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($product->variants as $variant)
                            <tr>
                                <td><strong>{{ $variant->size->name ?? '—' }}</strong></td>
                                <td>
                                    <span class="d-inline-flex align-items-center gap-2">
                                        @if ($variant->color && $variant->color->hex_code)
                                            <span class="d-inline-block rounded-circle border" style="width:16px;height:16px;background: {{ $variant->color->hex_code }};"></span>
                                        @endif
                                        {{ $variant->color->name ?? '—' }}
                                    </span>
                                </td>
                                <td><span class="font-mono text-sm">{{ $variant->sku }}</span></td>
                                <td>{{ $variant->stock }}</td>
                                <td>{{ $variant->price !== null ? '$' . number_format($variant->price, 2) : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state"><i class="fa-solid fa-layer-group"></i><strong>No variants</strong></div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
