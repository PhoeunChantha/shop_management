<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Categories') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Category table</p>
                <h3>All Categories</h3>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>New Category</span>
            </a>
        </div>

        <section class="premium-card" x-data="bulkSelect()">
            <x-table-loader />
            <x-bulk-bar :destroy="route('admin.categories.bulk-destroy')" :status="route('admin.categories.bulk-status')" noun="category" />

            <x-table-toolbar>
                <x-slot:left>
                    <x-per-page-selector :current="$perPage" />
                </x-slot:left>
                <x-slot:right>
                    <x-search-input name="search" placeholder="Search categories..." />
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
                            <th>Image</th>
                            <th>Icon</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Sort Order</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td class="bulk-check-col">
                                    <input type="checkbox" class="bulk-check" data-row-check value="{{ $category->id }}"
                                        x-model="selected" aria-label="Select row">
                                </td>
                                <td>
                                    <span class="muted-id">#{{ $category->id }}</span>
                                </td>
                                <td>
                                    @if ($category->image)
                                        <img src="{{ Imageurl($category->image ,'categories') }}" alt="image"
                                            class="w-10 h-10 object-cover rounded border dark:border-white/10">
                                    @else
                                        <span class="text-gray-300 dark:text-slate-600 text-xs">No Image</span>
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
                                        <span class="text-gray-400 dark:text-slate-500 text-xs italic">No
                                            description</span>
                                    @endif
                                </td>

                                <td>
                                    <span
                                        class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded border border-gray-200 dark:bg-white/10 dark:text-slate-200 dark:border-white/10">
                                        {{ $category->sort_order }}
                                    </span>
                                </td>
                                <td>
                                    @if ($category->status)
                                        <span
                                            class="text-green-600 bg-green-50 px-2 py-1 rounded text-xs font-medium border border-green-200 dark:text-emerald-300 dark:bg-emerald-500/10 dark:border-emerald-500/20">Enabled</span>
                                    @else
                                        <span
                                            class="text-red-600 bg-red-50 px-2 py-1 rounded text-xs font-medium border border-red-200 dark:text-red-300 dark:bg-red-500/10 dark:border-red-500/20">Disabled</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-group">
                                        <x-table-actions>
                                            <a href="{{ route('admin.categories.edit', $category->id) }}"
                                                class="table-actions__item table-actions__item--edit" role="menuitem">
                                                <i class="fa-solid fa-pen"></i>
                                                <span>Edit</span>
                                            </a>

                                            <button type="button" class="table-actions__item table-actions__item--danger"
                                                role="menuitem"
                                                data-delete-modal-target="deleteCategoryModal"
                                                data-delete-action="{{ route('admin.categories.destroy', $category->id) }}"
                                                data-delete-name="{{ $category->name }}">
                                                <i class="fa-solid fa-trash"></i>
                                                <span>Delete</span>
                                            </button>
                                        </x-table-actions>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-layer-group"></i>
                                        <strong>No categories found</strong>
                                        <span>Try a different search term or clear the current search.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$categories" label="categories" />
        </section>

        <x-delete-confirm-modal id="deleteCategoryModal" title="Delete this category?"
            message-after="from the system. This cannot be undone." />
    </div>
</x-app-layout>
