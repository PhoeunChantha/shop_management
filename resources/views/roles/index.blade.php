<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Roles') }}
        </h2>
    </x-slot>

    <div class="flex justify-end items-end px-5">

        <a href="{{ route('roles.create') }}" class="bg-slate-700 hover:bg-slate-800 text-white font-medium py-2 px-4 rounded transition-colors">
            Create
        </a>
    </div>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-message />
            <table class="w-full ">
                <thead class="bg-gray-100">
                    <tr class="border-b">
                        <th class="py-3 px-6 text-left w-[50px]">ID</th>
                        <th class="py-3 px-6 text-left w-[50px]">Name</th>
                        <th class="py-3 px-6 text-left ">Permissions</th>
                        <th class="py-3 px-6 text-left w-[150px]">Created At</th>
                        <th class="py-3 px-6 text-center w-[250px]">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @foreach ($roles as $role)
                    <tr class="border-b">
                        <td class="py-3 px-6 text-left">
                            {{ $role->id }}
                        </td>
                        <td class="py-3 px-6 text-left">
                            {{ $role->name }}
                        </td>
                        <td class="py-3 px-6 text-left">
                            @foreach ($role->permissions as $permission)
                                <span class="inline-block  text-gray-600 text-xs font-medium mr-2 px-2.5 py-0.5 rounded">
                                    {{ $permission->name }}
                                </span>
                            @endforeach
                        </td>
                        <td class="py-3 px-6 text-left">
                            {{ \Carbon\Carbon::parse($role->created_at)->format('d M, Y') }}
                        </td>
                        <td class="py-3 px-6 text-center">
                            <a href="{{ route('roles.edit', $role->id) }}" class="bg-slate-700 hover:bg-slate-600 text-white font-medium py-2 px-3 rounded mr-2">
                                Edit
                            </a>
                            <form action="{{ route('roles.destroy', $role->id) }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this role?');"
                                class="inline-block">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="bg-red-700 hover:bg-red-600 text-white font-medium py-2 px-3 rounded mr-2 transition">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">
                {{ $roles->links() }}
            </div>
        </div>
    </div>

</x-app-layout>