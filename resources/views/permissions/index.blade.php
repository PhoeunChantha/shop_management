<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Permissions') }}
        </h2>
    </x-slot>

    <div class="flex justify-end items-end px-5">

        <a href="{{ route('permissions.create') }}" class="bg-slate-700 hover:bg-slate-800 text-white font-medium py-2 px-4 rounded transition-colors">
            Create
        </a>
    </div>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-message />
            <table class="w-full ">
                <thead class="bg-gray-100">
                    <tr class="border-b">
                        <th class="py-3 px-6 text-left">ID</th>
                        <th class="py-3 px-6 text-left">Name</th>
                        <th class="py-3 px-6 text-left">Created At</th>
                        <th class="py-3 px-6 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @forelse ($permissions as $permission)
                    <tr class="border-b">
                        <th class="py-3 px-6 text-left">
                            {{ $permission->id }}
                        </th>
                        <th class="py-3 px-6 text-left">
                            {{ $permission->name }}
                        </th>
                        <th class="py-3 px-6 text-left">
                            {{ \Carbon\Carbon::parse($permission->created_at)->format('d M, Y') }}
                        </th>
                        <th class="py-3 px-6 text-center">
                            <a href="{{ route('permissions.edit', $permission->id) }}" class="bg-slate-700 hover:bg-slate-600 text-white font-medium py-2 px-3 rounded mr-2">
                                Edit
                            </a>
                            <form action="{{ route('permissions.destroy', $permission->id) }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this permission?');"
                                class="inline-block">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="bg-red-700 hover:bg-red-600 text-white font-medium py-2 px-3 rounded mr-2 transition">
                                    Delete
                                </button>
                            </form>
                        </th>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-4 px-6 text-center text-gray-500">
                            No permissions found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">
                {{ $permissions->links() }}
            </div>
        </div>
    </div>

</x-app-layout>