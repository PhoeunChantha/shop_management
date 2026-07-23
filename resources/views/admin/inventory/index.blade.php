<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Catalog</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Inventory') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Stock control</p>
                <h3>Inventory</h3>
            </div>
        </div>

        {{-- Filters --}}
        <x-filter-card :action="route('admin.inventory.index')" :grid="'grid grid-cols-1 sm:grid-cols-2 gap-3'">
            <x-slot:hidden>
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            </x-slot:hidden>

            <x-select name="stock" size="sm" label="Stock status" :value="request('stock')" placeholder="Any stock"
                :options="['in_stock' => 'In stock', 'low_stock' => 'Low stock', 'out_of_stock' => 'Out of stock']" />
        </x-filter-card>

        <x-admin.table-card class="mt-3 orders-panel">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" />
                    </x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="Search product or SKU..." />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Type</th>
                            <th style="width:110px;">On hand</th>
                            <th style="width:150px;">Status</th>
                            <th class="text-end" style="width:120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            @php
                                $isSingle = $product->product_type->value === 'single';
                                $total = $isSingle ? (int) $product->stock : (int) ($product->variants_sum_stock ?? 0);
                                $isLow = $isSingle
                                    ? ($product->low_stock_alert > 0 && $product->stock <= $product->low_stock_alert)
                                    : ($product->low_variants_count > 0);
                                if ($total <= 0) { $stClass = 'st-inactive'; $stLabel = 'Out of stock'; }
                                elseif ($isLow) { $stClass = 'st-new'; $stLabel = 'Low stock'; }
                                else { $stClass = 'st-active'; $stLabel = 'In stock'; }
                            @endphp
                            <tr>
                                <td>
                                    <div class="orders-cust">
                                        @if ($product->thumbnail)
                                            <img src="{{ Imageurl($product->thumbnail, 'products') }}" alt=""
                                                class="w-9 h-9 rounded-lg object-cover border dark:border-white/10" style="width:36px;height:36px;">
                                        @else
                                            <span class="orders-avatar" style="background:linear-gradient(135deg,#64748b,#334155);border-radius:10px;"><i class="fa-solid fa-box"></i></span>
                                        @endif
                                        <div>
                                            <div class="orders-cust__name">{{ $product->name }}</div>
                                            <div class="orders-cust__email">{{ $product->brand->name ?? '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-sm text-gray-600 dark:text-slate-300">
                                        {{ $isSingle ? 'Single' : $product->variants_count . ' variant' . ($product->variants_count === 1 ? '' : 's') }}
                                    </span>
                                </td>
                                <td class="dash-table__amt">{{ number_format($total) }}</td>
                                <td><span class="status-chip {{ $stClass }}">{{ $stLabel }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.inventory.show', $product->id) }}" class="orders-view">
                                        <i class="fa-solid fa-sliders"></i><span>Manage</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-admin.empty-state
                                        icon="fa-solid fa-warehouse"
                                        title="No products found"
                                        message="Adjust the search or stock filter."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
            </table>

            <x-slot:footer>
                <x-table-footer :paginator="$products" label="products" />
            </x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
