<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Content</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Collections') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Curated groups</p>
                <h3>Collections</h3>
            </div>
            <a href="{{ route('admin.collections.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i><span>New Collection</span>
            </a>
        </div>

        <x-admin.table-card class="mt-3 orders-panel" bulk>
            <x-slot:bulkBar>
                <x-bulk-bar :destroy="route('admin.collections.bulk-destroy')" :status="route('admin.collections.bulk-status')" noun="collection" />
            </x-slot:bulkBar>

            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" />
                    </x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="Search collections..." />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

                <table class="dash-table">
                    <thead>
                        <tr>
                            <th class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" @change="toggleAll($event)"
                                    :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="Select all">
                            </th>
                            <th style="width:80px;">Cover</th>
                            <th>Collection</th>
                            <th style="width:110px;">Products</th>
                            <th style="width:80px;">Order</th>
                            <th style="width:120px;">Status</th>
                            <th class="text-end" style="width:96px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($collections as $collection)
                            <tr>
                                <td class="bulk-check-col">
                                    <input type="checkbox" class="bulk-check" data-row-check value="{{ $collection->id }}"
                                        x-model="selected" aria-label="Select row">
                                </td>
                                <td>
                                    @if ($collection->image)
                                        <img src="{{ Imageurl($collection->image, 'collections') }}" alt=""
                                            class="rounded-lg object-cover border dark:border-white/10" style="width:48px;height:40px;">
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-lg bg-gray-100 text-gray-300 dark:bg-white/10" style="width:48px;height:40px;"><i class="fa-solid fa-layer-group"></i></span>
                                    @endif
                                </td>
                                <td>
                                    <div class="orders-cust__name">{{ $collection->name }}</div>
                                    @if ($collection->description)<div class="orders-cust__email">{{ Str::limit($collection->description, 60) }}</div>@endif
                                </td>
                                <td>
                                    <span class="count-pill">{{ $collection->products_count }}</span>
                                </td>
                                <td style="font-variant-numeric:tabular-nums;">{{ $collection->sort_order }}</td>
                                <td>
                                    <span class="status-chip {{ $collection->status ? 'st-active' : 'st-inactive' }}">
                                        {{ $collection->status ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <x-table-actions>
                                            <a href="{{ route('admin.collections.edit', $collection->id) }}"
                                                class="table-actions__item table-actions__item--edit" role="menuitem">
                                                <i class="fa-solid fa-pen"></i><span>Edit</span>
                                            </a>
                                            <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                                data-delete-modal-target="deleteCollectionModal"
                                                data-delete-action="{{ route('admin.collections.destroy', $collection->id) }}"
                                                data-delete-name="{{ $collection->name }}">
                                                <i class="fa-solid fa-trash"></i><span>Delete</span>
                                            </button>
                                        </x-table-actions>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <x-admin.empty-state icon="fa-solid fa-layer-group" title="No collections yet"
                                        message="Group products into a curated collection for the storefront." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            <x-slot:footer>
                <x-table-footer :paginator="$collections" label="collections" />
            </x-slot:footer>
        </x-admin.table-card>

        <x-delete-confirm-modal id="deleteCollectionModal" title="Delete this collection?"
            message-after="from the storefront. Products are not deleted. This cannot be undone." />
    </div>
</x-app-layout>
