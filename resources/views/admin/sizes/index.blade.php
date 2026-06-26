<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Sizes') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Size table</p>
                <h3>All Sizes</h3>
            </div>
            <a href="{{ route('admin.sizes.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>New Size</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card">
            <form method="GET" action="{{ route('admin.sizes.index') }}" class="table-toolbar">
                <div class="table-toolbar__left">
                    <div class="result-badge">
                        <i class="fa-solid fa-ruler-combined"></i>
                        <span>{{ $sizes->total() }} result{{ $sizes->total() === 1 ? '' : 's' }}</span>
                    </div>

                    <label class="per-page-control">
                        <span>Show</span>
                        <select name="per_page" onchange="this.form.submit()">
                            @foreach ([5, 10, 25, 50] as $sizeOption)
                            <option value="{{ $sizeOption }}" @selected($perPage===$sizeOption)>{{ $sizeOption }}</option>
                            @endforeach
                        </select>
                        <span>per page</span>
                    </label>
                </div>

                <label class="search-control">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search sizes..."
                        autocomplete="off" data-auto-search>
                </label>
            </form>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Sort Order</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sizes as $size)
                        <tr>
                            <td>
                                <span class="muted-id">#{{ $size->id }}</span>
                            </td>
                            <td>
                                <strong class="text-gray-900">{{ $size->name }}</strong>
                            </td>
                            <td>
                                <span class="text-sm text-gray-700 font-mono bg-gray-100 px-2 py-0.5 rounded border border-gray-200 font-bold">{{ $size->code }}</span>
                            </td>
                            <td>
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded border border-gray-200">
                                    {{ $size->sort_order }}
                                </span>
                            </td>
                            <td>
                                @if($size->status)
                                <span class="text-green-600 bg-green-50 px-2 py-1 rounded text-xs font-medium border border-green-200">Enabled</span>
                                @else
                                <span class="text-red-600 bg-red-50 px-2 py-1 rounded text-xs font-medium border border-red-200">Disabled</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-group">
                                    <a href="{{ route('admin.sizes.edit', $size->id) }}" class="table-action table-action--edit">
                                        <i class="fa-solid fa-pen"></i>
                                        <span>Edit</span>
                                    </a>

                                    <button type="button" class="table-action table-action--delete"
                                        data-delete-modal-target="deleteSizeModal"
                                        data-delete-action="{{ route('admin.sizes.destroy', $size->id) }}"
                                        data-delete-name="{{ $size->name }}">
                                        <i class="fa-solid fa-trash"></i>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fa-solid fa-ruler-combined"></i>
                                    <strong>No sizes found</strong>
                                    <span>Try a different search term or clear the current search.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$sizes" label="sizes" />
        </section>

        <x-delete-confirm-modal
            id="deleteSizeModal"
            title="Delete this size?"
            message-after="from the system. This cannot be undone." />
    </div>
</x-app-layout>