<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit User') }}
        </h2>
    </x-slot>
    <div class="flex justify-end items-end px-5">

        <a href="{{ route('users.index') }}" class="bg-slate-700 hover:bg-slate-800 text-white font-medium py-2 px-4 rounded transition-colors">
            Back
        </a>
    </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-message />
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                      
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">User Name</label>
                            <input value="{{ old('name', $user->name) }}" type="text" name="name" id="name" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block  shadow-sm sm:text-sm border-gray-300 w-1/2 rounded-lg " placeholder="Enter user name">
                            @error('name')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input value="{{ old('email', $user->email) }}" type="email" name="email" id="email" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block  shadow-sm sm:text-sm border-gray-300 w-1/2 rounded-lg " placeholder="Enter user email">
                            @error('email')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="avatar" class="block text-sm font-medium text-gray-700 mb-3">Avatar (Optional)</label>
                            @if($user->avatar)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="Current Avatar" class="w-20 h-20 rounded-full object-cover">
                                </div>
                            @endif
                            <input type="file" name="avatar" id="avatar" accept="image/*" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block shadow-sm sm:text-sm border-gray-300 w-1/2">
                            <p class="text-gray-500 text-sm mt-2">Allowed formats: JPEG, PNG, JPG, GIF (Max 2MB)</p>
                            @error('avatar')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Assign Roles</label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-2">
                                @foreach($roles as $role)
                                <div class="flex items-center">
                                    <input type="checkbox"
                                        name="roles[]"
                                        id="role_{{ $role->id }}"
                                        value="{{ $role->name }}"
                                        {{ in_array($role->name, old('roles', $hasRoles->toArray())) ? 'checked' : '' }}
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">

                                    <label for="role_{{ $role->id }}" class="ml-2 text-sm text-gray-900 select-none cursor-pointer">
                                        {{ $role->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>

                            @error('roles')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <x-primary-button>
                            {{ __('Update User') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>