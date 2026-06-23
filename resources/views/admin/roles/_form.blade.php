@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $selectedPermissions = old('permissions', $isEdit ? $hasPermissions->toArray() : []);
    $permissionGroups = [
        'role' => 'Role',
        'user' => 'Users',
        'permission' => 'Permission',
    ];
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body">
        <div class="form-field">
            <label for="name">Role Name</label>
            <input value="{{ old('name', $role->name ?? '') }}" type="text" name="name" id="name"
                class="form-input"
                placeholder="Enter role name">
            @error('name')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <div class="permission-section-title">
                <div>
                    <label>Assign Permissions</label>
                    <p>Select the actions this role can perform.</p>
                </div>
            </div>

            <div class="permission-groups">
                @foreach ($permissionGroups as $groupKey => $groupLabel)
                    @php
                        $groupPermissions = $permissions->filter(fn ($permission) => str_contains($permission->name, $groupKey));
                    @endphp

                    <div class="permission-group-row">
                        <label class="permission-group-toggle">
                            <input type="checkbox" data-permission-group-select="{{ $groupKey }}">
                            <span>{{ $groupLabel }}</span>
                        </label>

                        <div class="permission-group-options">
                            @foreach ($groupPermissions as $permission)
                                <label for="permission_{{ $permission->id }}" class="permission-option">
                                    <input type="checkbox" name="permissions[]" id="permission_{{ $permission->id }}"
                                        value="{{ $permission->id }}"
                                        {{ in_array($permission->id, $selectedPermissions) || in_array($permission->name, $selectedPermissions) ? 'checked' : '' }}
                                        class="permission-checkbox" data-permission-checkbox data-permission-group="{{ $groupKey }}">
                                    <span>{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @error('permissions')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="form-panel-footer">
        <a href="{{ route('roles.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
