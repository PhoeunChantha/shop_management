<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Marketing</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Create Deal') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Offer setup</p>
                <h3>New Deal Campaign</h3>
            </div>
            <a href="{{ route('admin.deals.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>Back</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-tags"></i></div>
                <div>
                    <p class="section-kicker">Offers & deals</p>
                    <h3>Campaign details</h3>
                    <p>Create a timed promotion and attach the products it should promote.</p>
                </div>
            </div>

            @include('admin.deals._form', [
                'mode' => 'create',
                'action' => route('admin.deals.store'),
                'submitText' => __('Create Deal'),
            ])
        </section>
    </div>
</x-app-layout>
