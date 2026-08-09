<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Content') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Edit Page') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Page setup') }}</p>
                <h3>{{ __('Edit Page') }}</h3>
            </div>
            <a href="{{ route('admin.pages.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>{{ __('Back') }}</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-file-lines"></i></div>
                <div>
                    <p class="section-kicker">{{ __('CMS page') }}</p>
                    <h3>{{ __('Page details') }}</h3>
                    <p>{{ __('Update this content page.') }}</p>
                </div>
            </div>

            @include('admin.pages._form', [
                'mode' => 'edit',
                'page' => $page,
                'action' => route('admin.pages.update', $page->id),
                'submitText' => __('Update Page'),
            ])
        </section>
    </div>
</x-app-layout>
