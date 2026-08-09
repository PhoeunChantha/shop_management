<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Catalog') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Reorder Alerts') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page inventory-reorder-page" x-data="{ selected: [] }">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Inventory planning') }}</p>
                <h3>{{ __('Reorder Alerts') }}</h3>
                <p class="text-secondary mb-0">{{ __('Review products and variants below their low-stock threshold.') }}</p>
            </div>
            <a href="{{ route('admin.inventory.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-warehouse"></i><span>{{ __('Inventory') }}</span>
            </a>
        </div>

        <x-message />

        <div class="reorder-stat-strip">
            <article><span>{{ __('Alerts') }}</span><strong>{{ number_format($stats['alerts']) }}</strong></article>
            <article><span>{{ __('Out of stock') }}</span><strong>{{ number_format($stats['out']) }}</strong></article>
            <article><span>{{ __('Low stock') }}</span><strong>{{ number_format($stats['low']) }}</strong></article>
            <article><span>{{ __('Suggested units') }}</span><strong>{{ number_format($stats['units']) }}</strong></article>
            <article><span>{{ __('Est. cost') }}</span><strong>${{ number_format($stats['cost'], 2) }}</strong></article>
        </div>

        <div class="reorder-layout">
            <x-admin.table-card class="reorder-alert-card">
                <x-slot:toolbar>
                    <x-table-toolbar>
                        <x-slot:left>
                            <x-per-page-selector :current="$perPage" />
                        </x-slot:left>
                        <x-slot:right>
                            <form method="GET" action="{{ route('admin.inventory.reorder') }}" class="toolbar-form">
                                <input type="hidden" name="per_page" value="{{ $perPage }}">
                                <x-select name="severity" size="sm" :value="request('severity')" placeholder="{{ __('Any alert') }}"
                                    :options="['out' => 'Out of stock', 'low' => 'Low stock']" />
                                <x-search-input name="search" placeholder="{{ __('Search product, SKU, brand...') }}" />
                            </form>
                        </x-slot:right>
                    </x-table-toolbar>
                </x-slot:toolbar>

                <div class="premium-table-wrap">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th style="width:44px;">
                                    <input type="checkbox" class="form-check-input"
                                        @change="selected = $event.target.checked ? @js($alerts->pluck('key')->values()) : []">
                                </th>
                                <th>{{ __('Item') }}</th>
                                <th>{{ __('SKU') }}</th>
                                <th>{{ __('Stock') }}</th>
                                <th>{{ __('Alert') }}</th>
                                <th>{{ __('Target') }}</th>
                                <th>{{ __('Reorder') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($alerts as $row)
                                <tr>
                                    <td>
                                        <input type="checkbox" form="reorder-po-form" name="selected[]" value="{{ $row->key }}" class="form-check-input"
                                            x-model="selected">
                                    </td>
                                    <td>
                                        <div class="orders-cust">
                                            @if ($row->thumbnail)
                                                <img src="{{ Imageurl($row->thumbnail, 'products') }}" alt=""
                                                    class="reorder-thumb">
                                            @else
                                                <span class="orders-avatar reorder-thumb"><i class="fa-solid fa-box"></i></span>
                                            @endif
                                            <div>
                                                <div class="orders-cust__name">{{ $row->name }}</div>
                                                <div class="orders-cust__email">{{ $row->label }} · {{ $row->brand }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="dash-table__id">{{ $row->sku ?: '-' }}</span></td>
                                    <td class="dash-table__amt">{{ number_format($row->stock) }}</td>
                                    <td>
                                        <input type="number" form="reorder-rules-form" name="rules[{{ $row->key }}][low_stock_alert]"
                                            class="reorder-rule-input" min="0" max="100000" value="{{ $row->alert }}">
                                    </td>
                                    <td>{{ number_format($row->target) }}</td>
                                    <td>
                                        <input type="number" form="reorder-po-form" name="quantities[{{ $row->key }}]" class="reorder-rule-input"
                                            min="1" max="100000" value="{{ $row->suggested_qty }}">
                                    </td>
                                    <td>
                                        <span class="status-chip {{ $row->severity === 'out' ? 'st-inactive' : 'st-new' }}">
                                            {{ $row->severity === 'out' ? 'Out of stock' : 'Low stock' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <x-admin.empty-state icon="fa-solid fa-circle-check" title="{{ __('No reorder alerts') }}"
                                            message="{{ __('Products with stock at or below their low-stock threshold will appear here.') }}" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-slot:footer>
                    <x-table-footer :paginator="$alerts" label="{{ __('alerts') }}" />
                </x-slot:footer>
            </x-admin.table-card>

            <aside class="premium-card reorder-po-panel">
                <form id="reorder-po-form" method="POST" action="{{ route('admin.inventory.reorder.purchase-order') }}">
                    @csrf
                    <div class="form-section__header">
                        <span class="form-section__icon"><i class="fa-solid fa-truck-ramp-box"></i></span>
                        <div>
                            <p class="section-kicker">{{ __('Purchase order') }}</p>
                            <h3>{{ __('Create from selected') }}</h3>
                        </div>
                    </div>
                    <div class="form-grid form-grid--single">
                        <x-select name="supplier_id" label="{{ __('Supplier') }}" :options="$suppliers" optionValue="id" optionLabel="name"
                            :value="old('supplier_id')" placeholder="{{ __('Select supplier') }}" searchable />
                        <x-select name="status" label="{{ __('Initial status') }}" :options="['draft' => 'Draft', 'ordered' => 'Ordered']"
                            :value="old('status', 'draft')" />
                        <div class="form-field">
                            <label for="expected_at">{{ __('Expected arrival') }}</label>
                            <input type="date" name="expected_at" id="expected_at" class="form-input" value="{{ old('expected_at') }}">
                        </div>
                    </div>
                    <div class="reorder-selection">
                        <span x-text="selected.length"></span>
                        <small>{{ __('selected alert(s)') }}</small>
                    </div>
                    <button type="submit" class="premium-button premium-button--dark w-100">
                        <i class="fa-solid fa-clipboard-list"></i><span>{{ __('Create purchase order') }}</span>
                    </button>
                    <button type="submit" form="reorder-rules-form" class="ghost-button ghost-button--panel w-100 mt-2">
                        <i class="fa-solid fa-sliders"></i><span>{{ __('Save rule changes') }}</span>
                    </button>
                </form>
            </aside>
        </div>

        <form id="reorder-rules-form" method="POST" action="{{ route('admin.inventory.reorder.rules') }}">
            @csrf
            @method('PATCH')
        </form>
    </div>
</x-app-layout>
