<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Content</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Create Collection') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Collection setup</p>
                <h3>New Collection</h3>
            </div>
            <a href="{{ route('admin.collections.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>Back</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-layer-group"></i></div>
                <div>
                    <p class="section-kicker">Curated group</p>
                    <h3>Collection details</h3>
                    <p>Group products into a curated collection for the storefront.</p>
                </div>
            </div>

            @include('admin.collections._form', [
                'mode' => 'create',
                'action' => route('admin.collections.store'),
                'submitText' => __('Create Collection'),
            ])
        </section>
    </div>
</x-app-layout>
