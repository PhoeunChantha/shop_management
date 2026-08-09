<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Content') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Edit Announcement') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Announcement setup') }}</p>
                <h3>{{ __('Edit Announcement') }}</h3>
            </div>
            <a href="{{ route('admin.announcements.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>{{ __('Back') }}</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-bullhorn"></i></div>
                <div>
                    <p class="section-kicker">{{ __('Top bar') }}</p>
                    <h3>{{ __('Announcement details') }}</h3>
                    <p>{{ __('Update this announcement bar message.') }}</p>
                </div>
            </div>

            @include('admin.announcements._form', [
                'mode' => 'edit',
                'announcement' => $announcement,
                'action' => route('admin.announcements.update', $announcement->id),
                'submitText' => __('Update Announcement'),
            ])
        </section>
    </div>
</x-app-layout>
