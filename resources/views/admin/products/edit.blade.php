<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Edit Product') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
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

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <p class="section-kicker">Update product</p>
                    <h3>Product details</h3>
                    <p>Modify product information, images, variants, stock, pricing and discount.</p>
                </div>
            </div>

            @include('admin.products._form', [
                'mode' => 'edit',
                'action' => route('admin.products.update', $product->id),
                'submitText' => __('Update Product'),
            ])
        </section>
    </div>
</x-app-layout>
