<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Configuration</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Edit Shipping Method') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Shipping setup</p>
                <h3>Edit Shipping Method</h3>
            </div>
            <a href="{{ route('admin.shipping.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>Back</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-truck"></i></div>
                <div>
                    <p class="section-kicker">Delivery option</p>
                    <h3>Shipping details</h3>
                    <p>Update this delivery option.</p>
                </div>
            </div>

            @include('admin.shipping._form', [
                'mode' => 'edit',
                'method' => $method,
                'action' => route('admin.shipping.update', $method->id),
                'submitText' => __('Update Shipping Method'),
            ])
        </section>
    </div>
</x-app-layout>
