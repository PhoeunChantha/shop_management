<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Colors') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Color table</p>
                <h3>All Colors</h3>
            </div>
            <a href="{{ route('admin.colors.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>New Color</span>
            </a>
        </div>

        <section class="premium-card" x-data="bulkSelect()">
            <x-table-loader />
            <x-bulk-bar :destroy="route('admin.colors.bulk-destroy')" :status="route('admin.colors.bulk-status')" noun="color" />

            <x-table-toolbar>
                <x-slot:left>
                    <x-per-page-selector :current="$perPage" />
                </x-slot:left>
                <x-slot:right>
                    <x-search-input name="search" placeholder="Search colors..." />
                </x-slot:right>
            </x-table-toolbar>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" @change="toggleAll($event)"
                                    :checked="allChecked" x-effect="$el.indeterminate = someChecked" aria-label="Select all">
                            </th>
                            <th>ID</th>
                            <th>Preview</th>
                            <th>Color Name</th>
                            <th>Color Code (Hex)</th>
                            <th>Sort Order</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($colors as $color)
                        <tr>
                            <td class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" data-row-check value="{{ $color->id }}"
                                    x-model="selected" aria-label="Select row">
                            </td>
                            <td>
                                <span class="muted-id">#{{ $color->id }}</span>
                            </td>
                            <td>
                                <div class="w-7 h-7 rounded-full border border-gray-300 shadow-sm dark:border-white/20" style="background-color: {{ $color->hex_code ?? '#FFFFFF' }};"></div>
                            </td>
                            <td>
                                <strong class="text-gray-900 dark:text-slate-100">{{ $color->name }}</strong>
                            </td>
                            <td>
                                <span class="text-sm text-gray-500 dark:text-slate-400 font-mono">{{ $color->code }}</span>
                            </td>
                            <td>
                                <span class="count-pill">{{ $color->sort_order }}</span>
                            </td>
                            <td>
                                <span class="status-chip {{ $color->status ? 'st-active' : 'st-inactive' }}">{{ $color->status ? 'Enabled' : 'Disabled' }}</span>
                            </td>
                            <td>
                                <div class="action-group">
                                    <x-table-actions>
                                        <a href="{{ route('admin.colors.edit', $color->id) }}" class="table-actions__item table-actions__item--edit" role="menuitem">
                                            <i class="fa-solid fa-pen"></i>
                                            <span>Edit</span>
                                        </a>

                                        <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                            data-delete-modal-target="deleteColorModal"
                                            data-delete-action="{{ route('admin.colors.destroy', $color->id) }}"
                                            data-delete-name="{{ $color->name }}">
                                            <i class="fa-solid fa-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </x-table-actions>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fa-solid fa-palette"></i>
                                    <strong>No colors found</strong>
                                    <span>Try a different search term or clear the current search.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$colors" label="colors" />
        </section>

        <x-delete-confirm-modal
            id="deleteColorModal"
            title="Delete this color?"
            message-after="from the system. This cannot be undone." />
    </div>
</x-app-layout>