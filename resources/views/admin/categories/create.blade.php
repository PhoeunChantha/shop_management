<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Create Category') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Category setup</p>
                <h3>New Category</h3>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div>
                    <p class="section-kicker">Create product category</p>
                    <h3>Category details</h3>
                    <p>Add a new category to organize your products efficiently.</p>
                </div>
            </div>

            @include('admin.categories._form', [
                'mode' => 'create',
                'action' => route('admin.categories.store'),
                'submitText' => __('Create Category'),
            ])
        </section>
    </div>
</x-app-layout>