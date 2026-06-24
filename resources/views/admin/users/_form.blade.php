@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $selectedRole = old('role', $isEdit ? $hasRoles->first() : '');
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="p-6 space-y-5">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">User Name</label>
            <input value="{{ old('name', $user->name ?? '') }}" type="text" name="name" id="name"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm"
                placeholder="Enter user name">
            @error('name')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input value="{{ old('email', $user->email ?? '') }}" type="email" name="email" id="email"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm"
                placeholder="Enter user email">
            @error('email')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        @unless ($isEdit)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <input type="password" name="password" id="password"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm"
                        placeholder="Enter password">
                    @error('password')
                        <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm"
                        placeholder="Confirm password">
                    @error('confirm_password')
                        <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endunless

        <div>
            <label for="avatar" class="block text-sm font-medium text-gray-700 mb-2">Avatar (Optional)</label>
            <div class="avatar-upload-field">
                @if ($isEdit && $user->avatar)
                    @php
                        $avatarUrl = str_contains($user->avatar, '/')
                            ? (str_starts_with($user->avatar, 'uploads/')
                                ? asset($user->avatar)
                                : asset('storage/' . $user->avatar))
                            : asset('uploads/users/' . $user->avatar);
                    @endphp
                    <img src="{{ $avatarUrl }}" alt="Current Avatar" class="avatar-upload-preview" data-avatar-preview>
                @else
                    <span class="avatar-upload-preview avatar-upload-preview--initial" data-avatar-initial>
                        {{ strtoupper(substr(old('name', $user->name ?? 'U'), 0, 1)) }}
                    </span>
                    <img src="" alt="Selected avatar preview" class="avatar-upload-preview" data-avatar-preview hidden>
                @endif
                <div class="avatar-upload-content">
                    <div class="avatar-upload-heading">
                        <p class="avatar-upload-title">Profile photo</p>
                        <p class="avatar-upload-help">JPEG, PNG, JPG, or GIF. Max 2MB.</p>
                    </div>
                    <label for="avatar" class="avatar-upload-trigger">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Choose image</span>
                    </label>
                    <input type="file" name="avatar" id="avatar" accept="image/*" class="avatar-upload-input" data-avatar-input>
                    <p class="avatar-upload-filename" data-avatar-filename>No file selected</p>
                </div>
            </div>
            @error('avatar')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="role" class="block text-sm font-medium text-gray-700 mb-1.5">Assign Role</label>
            <select name="role" id="role"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                <option value="">Select role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>
                        {{ ucfirst($role->name) }}
                    </option>
                @endforeach
            </select>
            @error('role')
                <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
        <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 px-4 py-2.5">Cancel</a>
        <button type="submit"
            class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium py-2.5 px-5 rounded-lg shadow-sm transition-colors">
            <i class="fa-solid fa-check text-xs"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
