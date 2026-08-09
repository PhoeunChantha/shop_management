<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Access Management') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Edit Permission') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Permission setup') }}</p>
                <h3>{{ __('Edit Permission') }}</h3>
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
                    <p class="section-kicker">{{ __('Update access permission') }}</p>
                    <h3>{{ __('Permission details') }}</h3>
                    <p>{{ __('Update the permission name used by role assignments.') }}</p>
                </div>
            </div>

            @include('admin.permissions._form', [
                'mode' => 'edit',
                'action' => route('admin.permissions.update', $permission->id),
                'permission' => $permission,
                'submitText' => __('Update Permission'),
            ])
        </section>
    </div>
</x-app-layout>
