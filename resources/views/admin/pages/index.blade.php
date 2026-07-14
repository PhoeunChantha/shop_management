<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Content</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Pages') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">CMS pages</p>
                <h3>Pages</h3>
            </div>
            <a href="{{ route('admin.pages.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i><span>New Page</span>
            </a>
        </div>

        <section class="premium-card mt-3 orders-panel" x-data="bulkSelect()">
            <x-table-loader />
            <x-bulk-bar :destroy="route('admin.pages.bulk-destroy')" :status="route('admin.pages.bulk-status')" noun="page" />

            <x-table-toolbar>
                <x-slot:left>
                    <x-per-page-selector :current="$perPage" />
                </x-slot:left>
                <x-slot:right>
                    <x-search-input name="search" placeholder="Search pages..." />
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
                            <th>Title</th>
                            <th>URL</th>
                            <th style="width:140px;">Updated</th>
                            <th style="width:120px;">Status</th>
                            <th class="text-end" style="width:96px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pages as $page)
                            <tr>
                                <td class="bulk-check-col">
                                    <input type="checkbox" class="bulk-check" data-row-check value="{{ $page->id }}"
                                        x-model="selected" aria-label="Select row">
                                </td>
                                <td><div class="orders-cust__name">{{ $page->title }}</div></td>
                                <td><span class="dash-table__id">/{{ $page->slug }}</span></td>
                                <td class="dash-table__date">{{ $page->updated_at?->format('M d, Y') }}</td>
                                <td>
                                    <span class="status-chip {{ $page->status ? 'st-active' : 'st-draft' }}">
                                        {{ $page->status ? 'Published' : 'Draft' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <x-table-actions>
                                            <a href="{{ route('admin.pages.edit', $page->id) }}"
                                                class="table-actions__item table-actions__item--edit" role="menuitem">
                                                <i class="fa-solid fa-pen"></i><span>Edit</span>
                                            </a>
                                            <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                                data-delete-modal-target="deletePageModal"
                                                data-delete-action="{{ route('admin.pages.destroy', $page->id) }}"
                                                data-delete-name="{{ $page->title }}">
                                                <i class="fa-solid fa-trash"></i><span>Delete</span>
                                            </button>
                                        </x-table-actions>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-file-lines"></i>
                                        <strong>No pages yet</strong>
                                        <span>Create content pages like About, Privacy or Terms.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$pages" label="pages" />
        </section>

        <x-delete-confirm-modal id="deletePageModal" title="Delete this page?"
            message-after="from the storefront. This cannot be undone." />
    </div>
</x-app-layout>
