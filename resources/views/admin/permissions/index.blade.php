<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Access Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Permissions') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Permission table</p>
                <h3>All Permissions</h3>
            </div>
            <a href="{{ route('permissions.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>New Permission</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card">
            <form method="GET" action="{{ route('permissions.index') }}" class="table-toolbar">
                <div class="table-toolbar__left">
                    <div class="result-badge">
                        <i class="fa-solid fa-key"></i>
                        <span>{{ $permissions->total() }} result{{ $permissions->total() === 1 ? '' : 's' }}</span>
                    </div>

                    <label class="per-page-control">
                        <span>Show</span>
                        <select name="per_page" onchange="this.form.submit()">
                            @foreach ([5, 10, 25, 50] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                        <span>per page</span>
                    </label>
                </div>

                <label class="search-control">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search permissions..."
                        autocomplete="off" data-auto-search>
                </label>
            </form>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Created At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permissions as $permission)
                            <tr>
                                <td>
                                    <span class="muted-id">#{{ $permission->id }}</span>
                                </td>
                                <td>
                                    <div class="permission-name-cell">
                                        <span><i class="fa-solid fa-key"></i></span>
                                        <strong>{{ $permission->name }}</strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="date-text">{{ \Carbon\Carbon::parse($permission->created_at)->format('d M, Y') }}</span>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="{{ route('permissions.edit', $permission->id) }}" class="table-action table-action--edit">
                                            <i class="fa-solid fa-pen"></i>
                                            <span>Edit</span>
                                        </a>

                                        <button type="button" class="table-action table-action--delete"
                                            data-delete-modal-target="deletePermissionModal"
                                            data-delete-action="{{ route('permissions.destroy', $permission->id) }}"
                                            data-delete-name="{{ $permission->name }}">
                                            <i class="fa-solid fa-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-key"></i>
                                        <strong>No permissions found</strong>
                                        <span>Try a different search term or clear the current search.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$permissions" label="permissions" />
        </section>

        <x-delete-confirm-modal
            id="deletePermissionModal"
            title="Delete this permission?"
            message-after="from the system. This cannot be undone."
        />
    </div>
</x-app-layout>
