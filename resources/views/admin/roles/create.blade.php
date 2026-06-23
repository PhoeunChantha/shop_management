<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Access Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Create Role') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Role setup</p>
                <h3>New Role</h3>
            </div>
            <a href="{{ route('roles.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <p class="section-kicker">Create access role</p>
                    <h3>Role details</h3>
                    <p>Create a role and attach the permissions it should control.</p>
                </div>
            </div>

            @include('admin.roles._form', [
                'mode' => 'create',
                'action' => route('roles.store'),
                'permissions' => $permissions,
                'submitText' => __('Create Role'),
            ])
        </section>
    </div>
</x-app-layout>
