<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create User') }}
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
                    <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                      
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">User Name</label>
                            <input value="{{ old('name') }}" type="text" name="name" id="name" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block shadow-sm sm:text-sm border-gray-300 w-1/2 rounded-lg" placeholder="Enter user name">
                            @error('name')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input value="{{ old('email') }}" type="email" name="email" id="email" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block shadow-sm sm:text-sm border-gray-300 w-1/2 rounded-lg" placeholder="Enter user email">
                            @error('email')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input type="password" name="password" id="password" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block shadow-sm sm:text-sm border-gray-300 w-1/2 rounded-lg" placeholder="Enter password">
                            @error('password')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block shadow-sm sm:text-sm border-gray-300 w-1/2 rounded-lg" placeholder="Confirm password">
                            @error('confirm_password')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Assign Roles</label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                                @foreach($roles as $role)
                                <div class="flex items-center">
                                    <input type="checkbox"
                                        name="roles[]"
                                        id="role_{{ $role->id }}"
                                        value="{{ $role->name }}"
                                        {{ is_array(old('roles')) && in_array($role->name, old('roles')) ? 'checked' : '' }}
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
                            {{ __('Create User') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>