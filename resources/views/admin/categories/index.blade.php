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

        <x-message />

        <section class="premium-card">
            <form method="GET" action="{{ route('admin.categories.index') }}" class="table-toolbar">
                <div class="table-toolbar__left">
                    <div class="result-badge">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>{{ $categories->total() }} result{{ $categories->total() === 1 ? '' : 's' }}</span>
                    </div>

                    <label class="per-page-control">
                        <span>Show</span>
                        <select name="per_page" onchange="this.form.submit()">
                            @foreach ([5, 10, 25, 50] as $size)
                            <option value="{{ $size }}" @selected($perPage===$size)>{{ $size }}</option>
                            @endforeach
                        </select>
                        <span>per page</span>
                    </label>
                </div>

                <label class="search-control">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search categories..."
                        autocomplete="off" data-auto-search>
                </label>
            </form>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
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
                            <td>
                                <span class="muted-id">#{{ $category->id }}</span>
                            </td>
                            <td>
                                @if($category->image)
                                <img src="{{ asset($category->image) }}" alt="image" class="w-10 h-10 object-cover rounded border">
                                @else
                                <span class="text-gray-300 text-xs">No Image</span>
                                @endif
                            </td>
                            <td>
                                @if($category->icon)
                                <span class="text-lg text-gray-700"><i class="fa-solid {{ $category->icon }}"></i></span>
                                @else
                                <span class="text-gray-300"><i class="fa-solid fa-icons"></i></span>
                                @endif
                            </td>
                            <td>
                                <strong class="text-gray-900">{{ $category->name }}</strong>
                            </td>
                            <td>
                                <span class="text-sm text-gray-500 font-mono">{{ $category->slug }}</span>
                            </td>
                            <td>
                                @if($category->description)
                                <span class="text-sm text-gray-600" title="{{ $category->description }}">
                                    {{ Str::limit($category->description, 50, '...') }}
                                </span>
                                @else
                                <span class="text-gray-400 text-xs italic">No description</span>
                                @endif
                            </td>

                            <td>
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded border border-gray-200">
                                    {{ $category->sort_order }}
                                </span>
                            </td>
                            <td>
                                @if($category->status)
                                <span class="text-green-600 bg-green-50 px-2 py-1 rounded text-xs font-medium border border-green-200">Enabled</span>
                                @else
                                <span class="text-red-600 bg-red-50 px-2 py-1 rounded text-xs font-medium border border-red-200">Disabled</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-group">
                                    <a href="{{ route('admin.categories.edit', $category->id) }}" class="table-action table-action--edit">
                                        <i class="fa-solid fa-pen"></i>
                                        <span>Edit</span>
                                    </a>

                                    <button type="button" class="table-action table-action--delete"
                                        data-delete-modal-target="deleteCategoryModal"
                                        data-delete-action="{{ route('admin.categories.destroy', $category->id) }}"
                                        data-delete-name="{{ $category->name }}">
                                        <i class="fa-solid fa-trash"></i>
                                        <span>Delete</span>
                                    </button>
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

        <x-delete-confirm-modal
            id="deleteCategoryModal"
            title="Delete this category?"
            message-after="from the system. This cannot be undone." />
    </div>
</x-app-layout>