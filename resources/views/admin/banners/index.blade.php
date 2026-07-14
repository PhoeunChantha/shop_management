<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Content</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Banners') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Homepage hero</p>
                <h3>Banners</h3>
            </div>
            <a href="{{ route('admin.banners.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i><span>New Banner</span>
            </a>
        </div>

        <section class="premium-card mt-3 orders-panel" x-data="bulkSelect()">
            <x-table-loader />
            <x-bulk-bar :destroy="route('admin.banners.bulk-destroy')" :status="route('admin.banners.bulk-status')" noun="banner" />

            <x-table-toolbar>
                <x-slot:left>
                    <x-per-page-selector :current="$perPage" />
                </x-slot:left>
                <x-slot:right>
                    <x-search-input name="search" placeholder="Search banners..." />
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
                            <th style="width:90px;">Preview</th>
                            <th>Banner</th>
                            <th>Call to action</th>
                            <th style="width:80px;">Order</th>
                            <th style="width:120px;">Status</th>
                            <th class="text-end" style="width:96px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($banners as $banner)
                            <tr>
                                <td class="bulk-check-col">
                                    <input type="checkbox" class="bulk-check" data-row-check value="{{ $banner->id }}"
                                        x-model="selected" aria-label="Select row">
                                </td>
                                <td>
                                    @if ($banner->image)
                                        <img src="{{ Imageurl($banner->image, 'banners') }}" alt=""
                                            class="rounded-lg object-cover border dark:border-white/10" style="width:64px;height:40px;">
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-lg bg-gray-100 text-gray-300 dark:bg-white/10" style="width:64px;height:40px;"><i class="fa-regular fa-image"></i></span>
                                    @endif
                                </td>
                                <td>
                                    @if ($banner->kicker)<div class="orders-cust__email" style="text-transform:uppercase;letter-spacing:0.04em;">{{ $banner->kicker }}</div>@endif
                                    <div class="orders-cust__name">{{ $banner->title }}</div>
                                    @if ($banner->subtitle)<div class="orders-cust__email">{{ Str::limit($banner->subtitle, 60) }}</div>@endif
                                </td>
                                <td>
                                    @if ($banner->cta_text)
                                        <span class="status-chip st-draft">{{ $banner->cta_text }}</span>
                                        <div class="orders-pay__method" style="text-transform:none;letter-spacing:0;">{{ $banner->cta_link ?: '—' }}</div>
                                    @else
                                        <span class="text-gray-400 dark:text-slate-500 text-xs italic">No button</span>
                                    @endif
                                </td>
                                <td style="font-variant-numeric:tabular-nums;">{{ $banner->sort_order }}</td>
                                <td>
                                    <span class="status-chip {{ $banner->status ? 'st-active' : 'st-inactive' }}">
                                        {{ $banner->status ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="action-group">
                                        <x-table-actions>
                                            <a href="{{ route('admin.banners.edit', $banner->id) }}"
                                                class="table-actions__item table-actions__item--edit" role="menuitem">
                                                <i class="fa-solid fa-pen"></i><span>Edit</span>
                                            </a>
                                            <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                                data-delete-modal-target="deleteBannerModal"
                                                data-delete-action="{{ route('admin.banners.destroy', $banner->id) }}"
                                                data-delete-name="{{ $banner->title }}">
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
                                        <i class="fa-solid fa-images"></i>
                                        <strong>No banners yet</strong>
                                        <span>Create your first hero slide for the storefront home page.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$banners" label="banners" />
        </section>

        <x-delete-confirm-modal id="deleteBannerModal" title="Delete this banner?"
            message-after="from the storefront. This cannot be undone." />
    </div>
</x-app-layout>
