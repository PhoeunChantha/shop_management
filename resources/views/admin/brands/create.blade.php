<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Product Management') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Create Brand') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">

        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Brand setup') }}</p>
                <h3>{{ __('New Brand') }}</h3>
            </div>
            <a href="{{ route('admin.brands.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i>
                <span>{{ __('Back') }}</span>
            </a>
        </div>

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon">
                    <i class="fa-solid fa-tags"></i>
                </div>
                <div>
                    <p class="section-kicker">{{ __('Create product brand') }}</p>
                    <h3>{{ __('Brand details') }}</h3>
                    <p>{{ __('Add a new brand to organize and filter your products efficiently.') }}</p>
                </div>
            </div>

            @include('admin.brands._form', [
                'mode' => 'create',
                'action' => route('admin.brands.store'),
                'submitText' => __('Create Brand'),
            ])
        </section>
    </div>
</x-app-layout>
