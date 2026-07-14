<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Content</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Create Announcement') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Announcement setup</p>
                <h3>New Announcement</h3>
            </div>
            <a href="{{ route('admin.announcements.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>Back</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-bullhorn"></i></div>
                <div>
                    <p class="section-kicker">Top bar</p>
                    <h3>Announcement details</h3>
                    <p>Add a message for the storefront announcement bar.</p>
                </div>
            </div>

            @include('admin.announcements._form', [
                'mode' => 'create',
                'action' => route('admin.announcements.store'),
                'submitText' => __('Create Announcement'),
            ])
        </section>
    </div>
</x-app-layout>
