<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Permission') }}
        </h2>
    </x-slot>
    <div class="flex justify-end items-end px-5">
        
        <a href="{{ route('permissions.index') }}" class="bg-slate-700 hover:bg-slate-800 text-white font-medium py-2 px-4 rounded transition-colors">
            Back
        </a>
    </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-message />
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('permissions.update', $permission->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_method" value="PUT">
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Permission Name</label>
                            <input value="{{ old('name', $permission->name) }}" type="text" name="name" id="name" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block  shadow-sm sm:text-sm border-gray-300 w-1/2 rounded-lg " placeholder="Enter permission name">
                            @error('name')
                                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-primary-button>
                            {{ __('Update Permission') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>