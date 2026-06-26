<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create User') }}
        </h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">
                <div class="flex items-center justify-between gap-4 px-6 py-5 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">New User</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Create an account and assign roles.</p>
                    </div>
                    <a href="{{ route('admin.users.index') }}"
                        class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 text-sm font-medium py-2 px-3 rounded-lg ring-1 ring-gray-300 hover:bg-gray-50 transition-colors">
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                        Back
                    </a>
                </div>

                @include('admin.users._form', [
                    'mode' => 'create',
                    'action' => route('admin.users.store'),
                    'roles' => $roles,
                    'submitText' => __('Create User'),
                ])
            </div>
        </div>
    </div>
</x-app-layout>
