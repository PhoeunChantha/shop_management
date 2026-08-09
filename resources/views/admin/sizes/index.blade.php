<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Product Management') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Sizes') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Size table') }}</p>
                <h3>{{ __('All Sizes') }}</h3>
            </div>
            <a href="{{ route('admin.sizes.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>{{ __('New Size') }}</span>
            </a>
        </div>

        <x-admin.table-card bulk>
            <x-slot:bulkBar>
                <x-bulk-bar :destroy="route('admin.sizes.bulk-destroy')" :status="route('admin.sizes.bulk-status')" noun="size" />
            </x-slot:bulkBar>

            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" />
                    </x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="{{ __('Search sizes...') }}" />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table">
                <thead>
                    <tr>
                        <th class="bulk-check-col">
                            <input type="checkbox" class="bulk-check" @change="toggleAll($event)"
                                :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="{{ __('Select all') }}">
                        </th>
                        <th>{{ __('ID') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Sort Order') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sizes as $size)
                        <tr>
                            <td class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" data-row-check value="{{ $size->id }}"
                                    x-model="selected" aria-label="{{ __('Select row') }}">
                            </td>
                            <td>
                                <span class="muted-id">#{{ $size->id }}</span>
                            </td>
                            <td>
                                <strong class="text-gray-900 dark:text-slate-100">{{ $size->name }}</strong>
                            </td>
                            <td>
                                <span class="text-sm text-gray-700 font-mono bg-gray-100 px-2 py-0.5 rounded border border-gray-200 font-bold dark:text-slate-200 dark:bg-white/10 dark:border-white/10">{{ $size->code }}</span>
                            </td>
                            <td>
                                <span class="count-pill">{{ $size->sort_order }}</span>
                            </td>
                            <td>
                                <span class="status-chip {{ $size->status ? 'st-active' : 'st-inactive' }}">{{ $size->status ? 'Enabled' : 'Disabled' }}</span>
                            </td>
                            <td>
                                <div class="action-group">
                                    <x-table-actions>
                                        <a href="{{ route('admin.sizes.edit', $size->id) }}" class="table-actions__item table-actions__item--edit" role="menuitem">
                                            <i class="fa-solid fa-pen"></i>
                                            <span>{{ __('Edit') }}</span>
                                        </a>

                                        <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                            data-delete-modal-target="deleteSizeModal"
                                            data-delete-action="{{ route('admin.sizes.destroy', $size->id) }}"
                                            data-delete-name="{{ $size->name }}">
                                            <i class="fa-solid fa-trash"></i>
                                            <span>{{ __('Delete') }}</span>
                                        </button>
                                    </x-table-actions>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-admin.empty-state
                                    icon="fa-solid fa-ruler-combined"
                                    title="{{ __('No sizes found') }}"
                                    message="{{ __('Try a different search term or clear the current search.') }}"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer>
                <x-table-footer :paginator="$sizes" label="{{ __('sizes') }}" />
            </x-slot:footer>
        </x-admin.table-card>

        <x-delete-confirm-modal
            id="deleteSizeModal"
            title="{{ __('Delete this size?') }}"
            message-after="{{ __('from the system. This cannot be undone.') }}" />
    </div>
</x-app-layout>
