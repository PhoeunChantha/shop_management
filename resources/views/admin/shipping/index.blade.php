<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Configuration</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Shipping Methods') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Delivery options</p>
                <h3>Shipping methods</h3>
            </div>
            <a href="{{ route('admin.shipping.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i><span>New Method</span>
            </a>
        </div>

        <section class="premium-card mt-3 orders-panel" x-data="bulkSelect()">
            <x-table-loader />
            <x-bulk-bar :destroy="route('admin.shipping.bulk-destroy')" :status="route('admin.shipping.bulk-status')" noun="method" />

            <x-table-toolbar>
                <x-slot:left>
                    <x-per-page-selector :current="$perPage" />
                </x-slot:left>
                <x-slot:right>
                    <x-search-input name="search" placeholder="Search methods..." />
                </x-slot:right>
            </x-table-toolbar>

            <div class="premium-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" @change="toggleAll($event)"
                                    :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="Select all">
                            </th>
                            <th>Method</th>
                            <th style="width:150px;">Type</th>
                            <th style="width:180px;">Cost</th>
                            <th style="width:80px;">Order</th>
                            <th style="width:120px;">Status</th>
                            <th class="text-end" style="width:96px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($methods as $method)
                            <tr>
                                <td class="bulk-check-col">
                                    <input type="checkbox" class="bulk-check" data-row-check value="{{ $method->id }}"
                                        x-model="selected" aria-label="Select row">
                                </td>
                                <td>
                                    <div class="orders-cust__name">{{ $method->name }}</div>
                                    @if ($method->delivery_time)<div class="orders-cust__email">{{ $method->delivery_time }}</div>@endif
                                </td>
                                <td><span class="status-chip {{ $method->type->badge() }}">{{ $method->type->label() }}</span></td>
                                <td class="dash-table__amt">
                                    @switch($method->type->value)
                                        @case('free') Free @break
                                        @case('free_over')
                                            ${{ number_format($method->rate, 2) }}
                                            <span class="orders-cust__email" style="display:inline;">· free over ${{ number_format($method->free_over_amount, 2) }}</span>
                                            @break
                                        @default ${{ number_format($method->rate, 2) }}
                                    @endswitch
                                </td>
                                <td style="font-variant-numeric:tabular-nums;">{{ $method->sort_order }}</td>
                                <td>
                                    <span class="status-chip {{ $method->status ? 'st-active' : 'st-inactive' }}">
                                        {{ $method->status ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <x-table-actions>
                                            <a href="{{ route('admin.shipping.edit', $method->id) }}"
                                                class="table-actions__item table-actions__item--edit" role="menuitem">
                                                <i class="fa-solid fa-pen"></i><span>Edit</span>
                                            </a>
                                            <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                                data-delete-modal-target="deleteShippingModal"
                                                data-delete-action="{{ route('admin.shipping.destroy', $method->id) }}"
                                                data-delete-name="{{ $method->name }}">
                                                <i class="fa-solid fa-trash"></i><span>Delete</span>
                                            </button>
                                        </x-table-actions>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-truck"></i>
                                        <strong>No shipping methods yet</strong>
                                        <span>Add a delivery option customers can choose at checkout.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$methods" label="methods" />
        </section>

        <x-delete-confirm-modal id="deleteShippingModal" title="Delete this shipping method?"
            message-after="from checkout. This cannot be undone." />
    </div>
</x-app-layout>
