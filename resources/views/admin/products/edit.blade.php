<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Edit Product') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page product-editor-page">
        <div class="page-section-header product-editor-toolbar">
            <div>
                <p class="section-kicker">Product setup</p>
                <h3>Edit Product: {{ $product->name }}</h3>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.products.show', $product->id) }}" class="ghost-button ghost-button--panel">
                    <i class="fa-solid fa-eye"></i>
                    <span>View</span>
                </a>
                <a href="{{ route('admin.products.index') }}" class="ghost-button ghost-button--panel">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        <x-message />

        @include('admin.products._form', [
            'mode' => 'edit',
            'action' => route('admin.products.update', $product->id),
            'submitText' => __('Update Product'),
        ])
    </div>
</x-app-layout>
