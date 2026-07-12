<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Content</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Announcement bar') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Top bar messages</p>
                <h3>Announcement bar</h3>
            </div>
            <a href="{{ route('admin.announcements.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i><span>New Announcement</span>
            </a>
        </div>

        <section class="premium-card mt-3 orders-panel" x-data="bulkSelect()">
            <x-table-loader />
            <x-bulk-bar :destroy="route('admin.announcements.bulk-destroy')" :status="route('admin.announcements.bulk-status')" noun="announcement" />

            <x-table-toolbar>
                <x-slot:left>
                    <x-per-page-selector :current="$perPage" />
                </x-slot:left>
                <x-slot:right>
                    <x-search-input name="search" placeholder="Search messages..." />
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
                            <th>Message</th>
                            <th>Link</th>
                            <th style="width:80px;">Order</th>
                            <th style="width:120px;">Status</th>
                            <th class="text-end" style="width:96px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($announcements as $announcement)
                            <tr>
                                <td class="bulk-check-col">
                                    <input type="checkbox" class="bulk-check" data-row-check value="{{ $announcement->id }}"
                                        x-model="selected" aria-label="Select row">
                                </td>
                                <td>
                                    <div class="orders-cust__name d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-bullhorn text-gray-300 dark:text-slate-500"></i>
                                        {{ $announcement->message }}
                                    </div>
                                </td>
                                <td>
                                    @if ($announcement->link)
                                        <span class="orders-pay__method" style="text-transform:none;letter-spacing:0;">{{ $announcement->link }}</span>
                                    @else
                                        <span class="text-gray-400 dark:text-slate-500 text-xs italic">No link</span>
                                    @endif
                                </td>
                                <td style="font-variant-numeric:tabular-nums;">{{ $announcement->sort_order }}</td>
                                <td>
                                    <span class="status-chip {{ $announcement->status ? 'st-active' : 'st-inactive' }}">
                                        {{ $announcement->status ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <x-table-actions>
                                            <a href="{{ route('admin.announcements.edit', $announcement->id) }}"
                                                class="table-actions__item table-actions__item--edit" role="menuitem">
                                                <i class="fa-solid fa-pen"></i><span>Edit</span>
                                            </a>
                                            <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                                data-delete-modal-target="deleteAnnouncementModal"
                                                data-delete-action="{{ route('admin.announcements.destroy', $announcement->id) }}"
                                                data-delete-name="{{ Str::limit($announcement->message, 30) }}">
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
                                        <i class="fa-solid fa-bullhorn"></i>
                                        <strong>No announcements yet</strong>
                                        <span>Add a message for the storefront top bar.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$announcements" label="announcements" />
        </section>

        <x-delete-confirm-modal id="deleteAnnouncementModal" title="Delete this announcement?"
            message-after="from the storefront. This cannot be undone." />
    </div>
</x-app-layout>
