<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Catalog') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Create Attribute') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Attribute setup') }}</p>
                <h3>{{ __('New Attribute') }}</h3>
            </div>
            <a href="{{ route('admin.attributes.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i>
                <span>{{ __('Back') }}</span>
            </a>
        </div>

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-tags"></i></div>
                <div>
                    <p class="section-kicker">{{ __('Create product attribute') }}</p>
                    <h3>{{ __('Attribute details') }}</h3>
                    <p>{{ __('Define an attribute (Size, Color, Material…) and the values products can pick from.') }}</p>
                </div>
            </div>

            @include('admin.attributes._form', [
                'mode' => 'create',
                'action' => route('admin.attributes.store'),
                'submitText' => __('Create Attribute'),
            ])
        </section>
    </div>
</x-app-layout>
