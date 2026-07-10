<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Content</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Create Banner') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Banner setup</p>
                <h3>New Banner</h3>
            </div>
            <a href="{{ route('admin.banners.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>Back</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-images"></i></div>
                <div>
                    <p class="section-kicker">Homepage hero</p>
                    <h3>Banner details</h3>
                    <p>Add a hero slide with a headline, copy and a call-to-action button.</p>
                </div>
            </div>

            @include('admin.banners._form', [
                'mode' => 'create',
                'action' => route('admin.banners.store'),
                'submitText' => __('Create Banner'),
            ])
        </section>
    </div>
</x-app-layout>
