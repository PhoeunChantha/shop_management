<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Access Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Users') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">User table</p>
                <h3>All Users</h3>
            </div>
            <a href="{{ route('admin.users.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>New User</span>
            </a>
        </div>
        <form method="GET" action="{{ route('admin.users.index') }}" class="filter-card">
            <div class="filter-card__header">
                <div>
                    <p class="section-kicker">Filters</p>
                    <h3>Refine user records</h3>
                </div>
                <a href="{{ route('admin.users.index') }}" class="ghost-button">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Reset</span>
                </a>
            </div>

            <div class="filter-grid">
                <label class="filter-field">
                    <span>Role</span>
                    <select name="role">
                        <option value="">All roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected(request('role') === $role->name)>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="filter-field">
                    <span>Created from</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}">
                </label>

                <label class="filter-field">
                    <span>Created to</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}">
                </label>

                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <button type="submit" class="filter-button">
                    <i class="fa-solid fa-sliders"></i>
                    <span>Apply Filters</span>
                </button>
            </div>
        </form>

        <section class="premium-card">
            <form method="GET" action="{{ route('admin.users.index') }}" class="table-toolbar">
                <input type="hidden" name="role" value="{{ request('role') }}">
                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ request('date_to') }}">

                <div class="table-toolbar__left">
                    <div class="result-badge">
                        <i class="fa-solid fa-users"></i>
                        <span>{{ $users->total() }} result{{ $users->total() === 1 ? '' : 's' }}</span>
                    </div>

                    <label class="per-page-control">
                        <span>Show</span>
                        <select name="per_page" onchange="this.form.submit()">
                            @foreach ([5, 10, 25, 50] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}
                                </option>
                            @endforeach
                        </select>
                        <span>per page</span>
                    </label>
                </div>

                <label class="search-control">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search users..."
                        autocomplete="off" data-auto-search>
                </label>
            </form>

            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Roles</th>
                            <th>Created At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>
                                    <span class="muted-id">#{{ $user->id }}</span>
                                </td>
                                <td>
                                    <div class="user-cell">
                                        @if ($user->avatar)
                                            @php
                                                $avatarUrl = str_contains($user->avatar, '/')
                                                    ? (str_starts_with($user->avatar, 'uploads/')
                                                        ? asset($user->avatar)
                                                        : asset('storage/' . $user->avatar))
                                                    : asset('uploads/users/' . $user->avatar);
                                            @endphp
                                            <img src="{{ $avatarUrl }}" alt="{{ $user->name }} avatar">
                                        @else
                                            <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        @endif
                                        <div>
                                            <strong>{{ $user->name }}</strong>
                                            <small>{{ $user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="role-stack">
                                        @forelse ($user->roles as $role)
                                            <span class="status-pill">{{ $role->name }}</span>
                                        @empty
                                            <span class="empty-pill">No role</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="date-text">{{ \Carbon\Carbon::parse($user->created_at)->format('d M, Y') }}</span>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="{{ route('admin.users.edit', $user->id) }}"
                                            class="table-action table-action--edit">
                                            <i class="fa-solid fa-pen"></i>
                                            <span>Edit</span>
                                        </a>

                                        @can('delete users')
                                            <button type="button" class="table-action table-action--delete"
                                                data-delete-modal-target="deleteUserModal"
                                                data-delete-action="{{ route('admin.users.destroy', $user->id) }}"
                                                data-delete-name="{{ $user->name }}">
                                                <i class="fa-solid fa-trash"></i>
                                                <span>Delete</span>
                                            </button>
                                        @endcan
                                        
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-users"></i>
                                        <strong>No users found</strong>
                                        <span>Try a different search term or clear the current filters.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$users" />
        </section>

        <x-delete-confirm-modal
            id="deleteUserModal"
            title="Delete this user?"
        />
    </div>

</x-app-layout>
