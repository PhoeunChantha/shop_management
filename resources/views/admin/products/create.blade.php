<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Create Product') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Product setup</p>
                <h3>New Product</h3>
            </div>
            <a href="{{ route('admin.products.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        @include('admin.products._form', [
            'mode' => 'create',
            'action' => route('admin.products.store'),
            'submitText' => __('Create Product'),
        ])
    </div>
</x-app-layout>
