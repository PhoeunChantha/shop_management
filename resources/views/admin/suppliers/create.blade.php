<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Restock</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('New Supplier') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <form method="POST" action="{{ route('admin.suppliers.store') }}">
            @csrf
            @include('admin.suppliers._form')
        </form>
    </div>
</x-app-layout>
