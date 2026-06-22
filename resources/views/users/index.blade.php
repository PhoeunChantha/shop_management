<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Users') }}
        </h2>
    </x-slot>

    <div class="flex justify-end items-end px-5">

        <a href="{{ route('users.create') }}" class="bg-slate-700 hover:bg-slate-800 text-white font-medium py-2 px-4 rounded transition-colors">
            Create
        </a>
    </div>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-message />
            <table class="w-full ">
                <thead class="bg-gray-100">
                    <tr class="border-b">
                        <th class="py-3 px-6 text-left ">ID</th>
                        <th class="py-3 px-6 text-left ">Avatar</th>
                        <th class="py-3 px-6 text-left ">Name</th>
                        <th class="py-3 px-6 text-left ">Email</th>
                        <th class="py-3 px-6 text-left ">Roles</th>
                        <th class="py-3 px-6 text-left ">Created At</th>
                        <th class="py-3 px-6 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @foreach ($users as $user)
                    <tr class="border-b">
                        <td class="py-3 px-6 text-left">
                            {{ $user->id }}
                        </td>
                        <td class="py-3 px-6 text-left">
                            @if($user->avatar)
                                <img src="{{ asset('storage/' . $user->avatar) }}" alt="Avatar" class="w-10 h-10 rounded-full object-cover">
                            @else
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-200 text-gray-600 font-semibold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-6 text-left">
                            {{ $user->name }}
                        </td>
                        <td class="py-3 px-6 text-left">
                            {{ $user->email }}
                        </td>
                        <td class="py-3 px-6 text-left">
                            {{ $user->roles->pluck('name')->implode(' ,') }}
                        </td>
                        <td class="py-3 px-6 text-left">
                            {{ \Carbon\Carbon::parse($user->created_at)->format('d M, Y') }}
                        </td>
                        <td class="py-3 px-6 text-center">
                            <a href="{{ route('users.edit', $user->id) }}" class="bg-slate-700 hover:bg-slate-600 text-white font-medium py-2 px-3 rounded mr-2">
                                Edit
                            </a>

                            @hasanyrole('admin|manager')
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this user?');"
                                class="inline-block">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="bg-red-700 hover:bg-red-600 text-white font-medium py-2 px-3 rounded mr-2 transition">
                                    Delete
                                </button>
                            </form>
                            @endhasanyrole
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>

</x-app-layout>