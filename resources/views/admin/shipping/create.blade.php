<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Configuration') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Create Shipping Method') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Shipping setup') }}</p>
                <h3>{{ __('New Shipping Method') }}</h3>
            </div>
            <a href="{{ route('admin.shipping.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>{{ __('Back') }}</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-truck"></i></div>
                <div>
                    <p class="section-kicker">{{ __('Delivery option') }}</p>
                    <h3>{{ __('Shipping details') }}</h3>
                    <p>{{ __('Define a delivery option and how its cost is calculated.') }}</p>
                </div>
            </div>

            @include('admin.shipping._form', [
                'mode' => 'create',
                'action' => route('admin.shipping.store'),
                'submitText' => __('Create Shipping Method'),
            ])
        </section>
    </div>
</x-app-layout>
