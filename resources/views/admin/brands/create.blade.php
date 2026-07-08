<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Create Brand') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">

        <div class="page-section-header">
            <div>
                <p class="section-kicker">Brand setup</p>
                <h3>New Brand</h3>
            </div>
            <a href="{{ route('admin.brands.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon">
                    <i class="fa-solid fa-tags"></i>
                </div>
                <div>
                    <p class="section-kicker">Create product brand</p>
                    <h3>Brand details</h3>
                    <p>Add a new brand to organize and filter your products efficiently.</p>
                </div>
            </div>

            @include('admin.brands._form', [
                'mode' => 'create',
                'action' => route('admin.brands.store'),
                'submitText' => __('Create Brand'),
            ])
        </section>
    </div>
</x-app-layout>
