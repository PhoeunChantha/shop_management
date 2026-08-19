<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Product Management') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Categories') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Category table') }}</p>
                <h3>{{ __('All Categories') }}</h3>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>{{ __('New Category') }}</span>
            </a>
        </div>

        <x-admin.table-card bulk>
            <x-slot:bulkBar>
                <x-bulk-bar :destroy="route('admin.categories.bulk-destroy')" :status="route('admin.categories.bulk-status')" noun="category" />
            </x-slot:bulkBar>

            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" />
                    </x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="{{ __('Search categories...') }}" />
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
                        <th>{{ __('Image') }}</th>
                        <th>{{ __('Icon') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Slug') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Sort Order') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td class="bulk-check-col">
                                <input type="checkbox" class="bulk-check" data-row-check value="{{ $category->id }}"
                                    x-model="selected" aria-label="{{ __('Select row') }}">
                            </td>
                            <td>
                                <span class="muted-id">#{{ $category->id }}</span>
                            </td>
                            <td>
                                @if ($category->image)
                                    <img src="{{ Imageurl($category->image ,'categories') }}" alt="image"
                                        class="w-10 h-10 object-cover rounded border dark:border-white/10">
                                @else
                                    <span class="text-gray-300 dark:text-slate-600 text-xs">{{ __('No Image') }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($category->icon)
                                    <span class="text-lg text-gray-700 dark:text-slate-300"><i
                                            class="fa-solid {{ $category->icon }}"></i></span>
                                @else
                                    <span class="text-gray-300 dark:text-slate-600"><i
                                            class="fa-solid fa-icons"></i></span>
                                @endif
                            </td>
                            <td>
                                <strong class="text-gray-900 dark:text-slate-100">{{ $category->name }}</strong>
                            </td>
                            <td>
                                <span
                                    class="text-sm text-gray-500 dark:text-slate-400 font-mono">{{ $category->slug }}</span>
                            </td>
                            <td>
                                @if ($category->description)
                                    <span class="text-sm text-gray-600 dark:text-slate-300"
                                        title="{{ $category->description }}">
                                        {{ Str::limit($category->description, 50, '...') }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-slate-500 text-xs italic">{{ __('No description') }}</span>
                                @endif
                            </td>

                            <td>
                                <span class="count-pill">{{ $category->sort_order }}</span>
                            </td>
                            <td>
                                <span class="status-chip {{ $category->status ? 'st-active' : 'st-inactive' }}">{{ $category->status ? 'Enabled' : 'Disabled' }}</span>
                            </td>
                            <td>
                                <div class="action-group">
                                    <x-table-actions>
                                        <a href="{{ route('admin.categories.edit', $category->id) }}"
                                            class="table-actions__item table-actions__item--edit" role="menuitem">
                                            <i class="fa-solid fa-pen"></i>
                                            <span>{{ __('Edit') }}</span>
                                        </a>

                                        <button type="button" class="table-actions__item table-actions__item--danger"
                                            role="menuitem"
                                            data-delete-modal-target="deleteCategoryModal"
                                            data-delete-action="{{ route('admin.categories.destroy', $category->id) }}"
                                            data-delete-name="{{ $category->name }}">
                                            <i class="fa-solid fa-trash"></i>
                                            <span>{{ __('Delete') }}</span>
                                        </button>
                                    </x-table-actions>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <x-admin.empty-state
                                    icon="fa-solid fa-layer-group"
                                    title="{{ __('No categories found') }}"
                                    message="{{ __('Try a different search term or clear the current search.') }}"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer>
                <x-table-footer :paginator="$categories" label="{{ __('categories') }}" />
            </x-slot:footer>
        </x-admin.table-card>

        <x-delete-confirm-modal id="deleteCategoryModal" title="{{ __('Delete this category?') }}"
            message-after="{{ __('from the system. This cannot be undone.') }}" />
    </div>
</x-app-layout>
