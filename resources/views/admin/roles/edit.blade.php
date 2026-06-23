<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Role') }}
        </h2>
    </x-slot>
    <div class="flex justify-end items-end px-5">

        <a href="{{ route('roles.index') }}" class="bg-slate-700 hover:bg-slate-800 text-white font-medium py-2 px-4 rounded transition-colors">
            Back
        </a>
    </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-message />
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('roles.update', $role->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Role Name</label>
                            <input value="{{ old('name', $role->name) }}" type="text" name="name" id="name" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block  shadow-sm sm:text-sm border-gray-300 w-1/2 rounded-lg " placeholder="Enter role name">
                            @error('name')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Assign Permissions</label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-2">
                                @foreach($permissions as $permission)
                                <div class="flex items-center">
                                    <input {{ $hasPermissions->contains($permission->name) ? 'checked' : '' }} type="checkbox"
                                        name="permissions[]"
                                        id="permission_{{ $permission->id }}"
                                        value="{{ $permission->id }}"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">

                                    <label for="permission_{{ $permission->id }}" class="ml-2 text-sm text-gray-900 select-none cursor-pointer">
                                        {{ $permission->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>

                            @error('permissions')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <x-primary-button>
                            {{ __('Update Role') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>