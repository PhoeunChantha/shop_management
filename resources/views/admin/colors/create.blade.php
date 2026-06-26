<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Product Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Create Color') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Color setup</p>
                <h3>New Color</h3>
            </div>
            <a href="{{ route('admin.colors.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon">
                    <i class="fa-solid fa-palette"></i>
                </div>
                <div>
                    <p class="section-kicker">Create product color</p>
                    <h3>Color details</h3>
                    <p>Add a new color to manage your product variations efficiently.</p>
                </div>
            </div>

            @include('admin.colors._form', [
                'mode' => 'create',
                'action' => route('admin.colors.store'),
                'submitText' => __('Create Color'),
            ])
        </section>
    </div>
</x-app-layout>