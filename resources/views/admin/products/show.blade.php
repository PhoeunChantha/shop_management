<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Product Management') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Product Detail') }}
            </h2>
        </div>
    </x-slot>

    @php
        $statusMap = [
            'active' => 'st-active',
            'draft' => 'st-draft',
            'inactive' => 'st-inactive',
            'archived' => 'st-archived',
        ];
        $stockTotal = $product->isSingle() ? $product->stock : $product->variants->sum('stock');
        $discountLabel = null;
        if ($product->has_discount) {
            $discountLabel = $product->discount_type === 'percentage'
                ? rtrim(rtrim(number_format($product->discount_amount, 2), '0'), '.') . '%'
                : '$' . number_format($product->discount_amount, 2);
        }
    @endphp

    <div class="admin-page pd-page">

        {{-- Header --}}
        <div class="pd-header">
            <div class="pd-header__main">
                <p class="section-kicker mb-1">{{ __('Product detail') }}</p>
                <h2 class="pd-title">{{ $product->name }}</h2>
                <div class="pd-meta">
                    <span class="status-chip {{ $statusMap[$product->status] ?? 'st-draft' }}">{{ ucfirst($product->status) }}</span>
                    <span><i class="fa-solid fa-layer-group"></i>{{ $product->category->name ?? __('Uncategorized') }}</span>
                    <span><i class="fa-solid fa-tag"></i>{{ $product->brand->name ?? __('No brand') }}</span>
                    <span><i class="fa-solid fa-cubes-stacked"></i>{{ $stockTotal }} {{ __('in stock') }}</span>
                </div>
            </div>
            <div class="pd-header__actions">
                <a href="{{ route('admin.products.edit', $product->id) }}" class="premium-button premium-button--dark">
                    <i class="fa-solid fa-pen"></i><span>{{ __('Edit') }}</span>
                </a>
                <a href="{{ route('admin.products.index') }}" class="ghost-button ghost-button--panel">
                    <i class="fa-solid fa-arrow-left"></i><span>{{ __('Back') }}</span>
                </a>
            </div>
        </div>

        <x-message />

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

            {{-- Left rail --}}
            <div class="lg:col-span-2 d-flex flex-column gap-4">

                {{-- Gallery --}}
                <section class="pd-card">
                    <div class="pd-card__head">
                        <p class="section-kicker mb-0">{{ __('Gallery') }}</p>
                        @if ($product->images->isNotEmpty())
                            <span class="pd-count">{{ $product->images->count() }} {{ __('photos') }}</span>
                        @endif
                    </div>
                    @if ($product->thumbnail_url)
                        <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="pd-cover">
                    @else
                        <div class="empty-state"><i class="fa-regular fa-image"></i><strong>{{ __('No images') }}</strong></div>
                    @endif
                    @if ($product->images->isNotEmpty())
                        <div class="pd-thumbs">
                            @foreach ($product->images as $img)
                                <img src="{{ Imageurl($img->image, 'products') }}" alt="image"
                                    class="{{ $img->is_primary ? 'is-primary' : '' }}">
                            @endforeach
                        </div>
                    @endif
                </section>

                {{-- Pricing --}}
                <section class="pd-card">
                    <div class="pd-card__head">
                        <p class="section-kicker mb-0">{{ __('Pricing') }}</p>
                        @if ($product->has_discount)
                            <span class="pill-badge pill-sale">-{{ $discountLabel }}</span>
                        @endif
                    </div>
                    <div class="pd-price__final">${{ number_format($product->final_price, 2) }} <small>{{ __('final') }}</small></div>
                    @if ($product->has_discount)
                        <div class="pd-price__sale">
                            <del>${{ number_format($product->price, 2) }}</del>
                        </div>
                    @endif
                    <dl class="pd-rows">
                        <div class="pd-row">
                            <dt>{{ __('Regular price') }}</dt>
                            <dd>${{ number_format($product->price, 2) }}</dd>
                        </div>
                        <div class="pd-row">
                            <dt>{{ __('Cost price') }}</dt>
                            <dd>{{ $product->cost_price !== null ? '$' . number_format($product->cost_price, 2) : '—' }}</dd>
                        </div>
                        <div class="pd-row">
                            <dt>{{ __('Margin') }}</dt>
                            <dd>{{ $product->cost_price !== null ? '$' . number_format(max(0, $product->final_price - $product->cost_price), 2) : '—' }}</dd>
                        </div>
                    </dl>
                </section>

                {{-- Inventory --}}
                <section class="pd-card">
                    <div class="pd-card__head">
                        <p class="section-kicker mb-0">{{ __('Inventory') }}</p>
                    </div>
                    <div class="pd-stock__num">{{ $stockTotal }} <small>{{ __('units') }}</small></div>
                    <dl class="pd-rows">
                        <div class="pd-row">
                            <dt>{{ __('SKU') }}</dt>
                            <dd class="font-mono">{{ $product->sku ?: '—' }}</dd>
                        </div>
                        <div class="pd-row">
                            <dt>{{ __('Low stock alert') }}</dt>
                            <dd>{{ $product->low_stock_alert }}</dd>
                        </div>
                        <div class="pd-row">
                            <dt>{{ __('Weight') }}</dt>
                            <dd>{{ $product->weight !== null ? $product->weight . ' kg' : '—' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            {{-- Right rail --}}
            <div class="lg:col-span-3 d-flex flex-column gap-4">

                {{-- Details --}}
                <section class="pd-card">
                    <div class="pd-card__head">
                        <p class="section-kicker mb-0">{{ __('Details') }}</p>
                        <div class="d-flex flex-wrap gap-1">
                            @if ($product->is_featured)<span class="pill-badge pill-featured">{{ __('Featured') }}</span>@endif
                            @if ($product->is_new)<span class="pill-badge pill-new">{{ __('New') }}</span>@endif
                            @if ($product->is_best_seller)<span class="pill-badge pill-best">{{ __('Best Seller') }}</span>@endif
                            @if ($product->is_on_sale)<span class="pill-badge pill-sale">{{ __('On Sale') }}</span>@endif
                        </div>
                    </div>

                    <dl class="pd-facts">
                        <div class="pd-fact">
                            <dt>{{ __('Slug') }}</dt>
                            <dd class="font-mono">{{ $product->slug }}</dd>
                        </div>
                        <div class="pd-fact">
                            <dt>{{ __('Category') }}</dt>
                            <dd>{{ $product->category->name ?? '—' }}{{ $product->subCategory ? ' / ' . $product->subCategory->name : '' }}</dd>
                        </div>
                        <div class="pd-fact">
                            <dt>{{ __('Brand') }}</dt>
                            <dd>{{ $product->brand->name ?? '—' }}</dd>
                        </div>
                        <div class="pd-fact">
                            <dt>{{ __('SKU') }}</dt>
                            <dd class="font-mono">{{ $product->sku ?: '—' }}</dd>
                        </div>
                    </dl>

                    @if ($product->tags->isNotEmpty())
                        <div class="pd-tags">
                            @foreach ($product->tags as $tag)
                                <span class="tag-chip is-static">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if ($product->short_description || $product->description)
                        <div class="pd-notes">
                            @if ($product->short_description)
                                <p class="section-kicker mb-1">{{ __('Short description') }}</p>
                                <p class="pd-notes__text">{{ $product->short_description }}</p>
                            @endif
                            @if ($product->description)
                                <p class="section-kicker mt-3 mb-1">{{ __('Description') }}</p>
                                <p class="pd-notes__text">{{ $product->description }}</p>
                            @endif
                        </div>
                    @endif
                </section>

                {{-- Specifications --}}
                @if ($product->specifications->isNotEmpty())
                    <section class="pd-card">
                        <div class="pd-card__head">
                            <p class="section-kicker mb-0">{{ __('Specifications') }}</p>
                        </div>
                        <dl class="pd-rows">
                            @foreach ($product->specifications as $spec)
                                <div class="pd-row">
                                    <dt>{{ $spec->name }}</dt>
                                    <dd>{{ $spec->value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif

                {{-- SEO --}}
                @if ($product->seo_title || $product->seo_description)
                    <section class="pd-card">
                        <div class="pd-card__head">
                            <p class="section-kicker mb-0">{{ __('SEO') }}</p>
                        </div>
                        <div class="pd-seo">
                            @if ($product->seo_title)
                                <div>
                                    <strong>{{ __('Meta title') }}</strong>
                                    <p>{{ $product->seo_title }}</p>
                                </div>
                            @endif
                            @if ($product->seo_description)
                                <div>
                                    <strong>{{ __('Meta description') }}</strong>
                                    <p>{{ $product->seo_description }}</p>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif
            </div>
        </div>

        {{-- Variants --}}
        @if (! $product->isSingle())
            <section class="pd-card">
                <div class="pd-card__head">
                    <p class="section-kicker mb-0">{{ __('Variants') }}</p>
                    <span class="pd-count">{{ $product->variants->count() }} {{ __('items') }}</span>
                </div>
                <div class="premium-table-wrap">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>{{ __('Image') }}</th>
                                <th>{{ __('Variant') }}</th>
                                <th>{{ __('SKU') }}</th>
                                <th>{{ __('Barcode') }}</th>
                                <th>{{ __('Stock') }}</th>
                                <th>{{ __('Price') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($product->variants as $variant)
                                <tr>
                                    <td>
                                        @if ($variant->image_url)
                                            <img src="{{ $variant->image_url }}" alt="" class="pd-variant-img">
                                        @else
                                            <span class="pd-variant-ph"><i class="fa-regular fa-image"></i></span>
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
                                                <span class="text-gray-400">—</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td><span class="font-mono text-sm">{{ $variant->sku }}</span></td>
                                    <td><span class="font-mono text-sm text-gray-500">{{ $variant->barcode ?: '—' }}</span></td>
                                    <td>
                                        {{ $variant->stock }}
                                        @if ($variant->is_low_stock)<span class="pill-badge pill-sale ms-1">{{ __('Low') }}</span>@endif
                                    </td>
                                    <td>{{ $variant->price !== null ? '$' . number_format($variant->price, 2) : '$' . number_format($product->price, 2) }}</td>
                                    <td><span class="status-chip {{ $variant->status ? 'st-active' : 'st-inactive' }}">{{ $variant->status ? __('Active') : __('Inactive') }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state"><i class="fa-solid fa-layer-group"></i><strong>{{ __('No variants') }}</strong></div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
