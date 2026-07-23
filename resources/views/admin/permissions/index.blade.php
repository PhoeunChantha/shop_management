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
            <a href="{{ route('admin.permissions.create') }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-plus"></i>
                <span>New Permission</span>
            </a>
        </div>

        <x-admin.table-card>
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left>
                        <x-per-page-selector :current="$perPage" />
                    </x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="Search permissions..." />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

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
                                    <x-table-actions>
                                        <a href="{{ route('admin.permissions.edit', $permission->id) }}" class="table-actions__item table-actions__item--edit" role="menuitem">
                                            <i class="fa-solid fa-pen"></i>
                                            <span>Edit</span>
                                        </a>

                                        @can('delete permission')
                                            <button type="button" class="table-actions__item table-actions__item--danger" role="menuitem"
                                                data-delete-modal-target="deletePermissionModal"
                                                data-delete-action="{{ route('admin.permissions.destroy', $permission->id) }}"
                                                data-delete-name="{{ $permission->name }}">
                                                <i class="fa-solid fa-trash"></i>
                                                <span>Delete</span>
                                            </button>
                                        @endcan
                                    </x-table-actions>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-admin.empty-state
                                    icon="fa-solid fa-key"
                                    title="No permissions found"
                                    message="Try a different search term or clear the current search."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer>
                <x-table-footer :paginator="$permissions" label="permissions" />
            </x-slot:footer>
        </x-admin.table-card>

        <x-delete-confirm-modal
            id="deletePermissionModal"
            title="Delete this permission?"
            message-after="from the system. This cannot be undone."
        />
    </div>
</x-app-layout>
