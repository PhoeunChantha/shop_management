<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Access Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Roles') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Role table</p>
                <h3>All Roles</h3>
            </div>
            <a href="{{ route('roles.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>New Role</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card">
            <form method="GET" action="{{ route('roles.index') }}" class="table-toolbar">
                <div class="table-toolbar__left">
                    <div class="result-badge">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>{{ $roles->total() }} result{{ $roles->total() === 1 ? '' : 's' }}</span>
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
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search roles..."
                        autocomplete="off" data-auto-search>
                </label>
            </form>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Permissions</th>
                            <th>Created At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td>
                                    <span class="muted-id">#{{ $role->id }}</span>
                                </td>
                                <td>
                                    <div class="role-name-cell">
                                        <span><i class="fa-solid fa-shield-halved"></i></span>
                                        <strong>{{ $role->name }}</strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="role-stack">
                                        @forelse ($role->permissions as $permission)
                                            <span class="status-pill status-pill--neutral">{{ $permission->name }}</span>
                                        @empty
                                            <span class="empty-pill">No permissions</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <span class="date-text">{{ \Carbon\Carbon::parse($role->created_at)->format('d M, Y') }}</span>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="{{ route('roles.edit', $role->id) }}" class="table-action table-action--edit">
                                            <i class="fa-solid fa-pen"></i>
                                            <span>Edit</span>
                                        </a>

                                        <button type="button" class="table-action table-action--delete"
                                            data-delete-modal-target="deleteRoleModal"
                                            data-delete-action="{{ route('roles.destroy', $role->id) }}"
                                            data-delete-name="{{ $role->name }}">
                                            <i class="fa-solid fa-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-shield-halved"></i>
                                        <strong>No roles found</strong>
                                        <span>Try a different search term or clear the current filters.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$roles" label="roles" />
        </section>

        <x-delete-confirm-modal
            id="deleteRoleModal"
            title="Delete this role?"
            message-after="and its permission assignments. This cannot be undone."
        />
    </div>
</x-app-layout>
