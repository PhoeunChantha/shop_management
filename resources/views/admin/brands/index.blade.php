<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Product Management') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Brands') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Brand table') }}</p>
                <h3>{{ __('All Brands') }}</h3>
            </div>
            <button type="button" class="premium-button premium-button--dark"
                onclick="openBrandModal({ mode: 'create' })">
                <i class="fa-solid fa-plus"></i>
                <span>{{ __('New Brand') }}</span>
            </button>
        </div>

        <x-admin.table-card bulk>
            <x-slot:bulkBar>
                <x-bulk-bar :destroy="route('admin.brands.bulk-destroy')" :status="route('admin.brands.bulk-status')" noun="brand" />
            </x-slot:bulkBar>

            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" />
                    </x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="{{ __('Search brands...') }}" />
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
                        <th style="width:70px;">{{ __('ID') }}</th>
                        <th style="width:80px;">{{ __('Logo') }}</th>
                        <th style="width:26%;">{{ __('Brand Name') }}</th>
                        <th>{{ __('Slug') }}</th>
                        <th style="width:110px;">{{ __('Products') }}</th>
                        <th style="width:120px;">{{ __('Status') }}</th>
                        <th class="text-end" style="width:150px;">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr>
                            <td class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" data-row-check value="{{ $brand->id }}"
                                    x-model="selected" aria-label="{{ __('Select row') }}">
                            </td>
                            <td>
                                <span class="muted-id">#{{ $brand->id }}</span>
                            </td>
                            <td>
                                @if ($brand->image)
                                    <img src="{{ Imageurl($brand->image, 'brands') }}" alt="{{ $brand->name }}"
                                        class="w-10 h-10 object-cover rounded border dark:border-white/10">
                                @else
                                    <span
                                        class="d-inline-flex align-items-center justify-content-center rounded bg-gray-100 text-gray-300 dark:bg-white/10"
                                        style="width:40px;height:40px;"><i class="fa-solid fa-tag"></i></span>
                                @endif
                            </td>
                            <td>
                                <strong
                                    class="text-sm text-gray-800 dark:text-slate-200">{{ $brand->name }}</strong>
                            </td>
                            <td>
                                <span class="text-xs text-gray-400 dark:text-slate-500">{{ $brand->slug }}</span>
                            </td>
                            <td>
                                <span class="count-pill">{{ $brand->products_count }}</span>
                            </td>
                            <td>
                                <span class="status-chip {{ $brand->status ? 'st-active' : 'st-inactive' }}">{{ $brand->status ? 'Enabled' : 'Disabled' }}</span>
                            </td>
                            <td>
                                <div class="action-group">
                                    <x-table-actions>
                                        <button type="button" class="table-actions__item table-actions__item--edit"
                                            data-action="{{ route('admin.brands.update', $brand->id) }}"
                                            data-name="{{ $brand->name }}"
                                            data-status="{{ $brand->status ? '1' : '0' }}"
                                            data-image="{{ $brand->image ? Imageurl($brand->image, 'brands') : '' }}"
                                            onclick="openBrandModal({ mode: 'edit', action: this.dataset.action, name: this.dataset.name, status: this.dataset.status, image: this.dataset.image || null })">
                                            <i class="fa-solid fa-pen"></i>
                                            <span>{{ __('Edit') }}</span>
                                        </button>

                                        <button type="button" class="table-actions__item table-actions__item--danger"
                                            data-delete-modal-target="deleteBrandModal"
                                            data-delete-action="{{ route('admin.brands.destroy', $brand->id) }}"
                                            data-delete-name="{{ $brand->name }}">
                                            <i class="fa-solid fa-trash"></i>
                                            <span>{{ __('Delete') }}</span>
                                        </button>
                                    </x-table-actions>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-admin.empty-state
                                    icon="fa-solid fa-tags"
                                    title="{{ __('No brands found') }}"
                                    message="{{ __('Create your first brand or adjust the current search.') }}"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer>
                <x-table-footer :paginator="$brands" label="{{ __('brands') }}" />
            </x-slot:footer>
        </x-admin.table-card>

        <x-delete-confirm-modal id="deleteBrandModal" title="{{ __('Delete this brand?') }}"
            message-after="{{ __('from the system. This cannot be undone.') }}" />

        @include('admin.brands._modal')
    </div>
</x-app-layout>
