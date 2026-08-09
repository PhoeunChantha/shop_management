<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Access Management') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Create Permission') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Permission setup') }}</p>
                <h3>{{ __('New Permission') }}</h3>
            </div>
            <a href="{{ route('admin.permissions.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i>
                <span>{{ __('Back') }}</span>
            </a>
        </div>

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div>
                    <p class="section-kicker">{{ __('Create access permission') }}</p>
                    <h3>{{ __('Permission details') }}</h3>
                    <p>{{ __('Add a granular action that can be assigned to roles.') }}</p>
                </div>
            </div>

            @include('admin.permissions._form', [
                'mode' => 'create',
                'action' => route('admin.permissions.store'),
                'submitText' => __('Create Permission'),
            ])
        </section>
    </div>
</x-app-layout>
