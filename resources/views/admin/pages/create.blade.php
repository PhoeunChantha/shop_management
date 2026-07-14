<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Content</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Create Page') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Page setup</p>
                <h3>New Page</h3>
            </div>
            <a href="{{ route('admin.pages.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>Back</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-file-lines"></i></div>
                <div>
                    <p class="section-kicker">CMS page</p>
                    <h3>Page details</h3>
                    <p>Create a content page for the storefront (About, Privacy, Terms…).</p>
                </div>
            </div>

            @include('admin.pages._form', [
                'mode' => 'create',
                'action' => route('admin.pages.store'),
                'submitText' => __('Create Page'),
            ])
        </section>
    </div>
</x-app-layout>
