<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Marketing') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Edit Deal') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Offer setup') }}</p>
                <h3>{{ __('Edit Deal Campaign') }}</h3>
            </div>
            <a href="{{ route('admin.deals.show', $deal) }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>{{ __('Back') }}</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-tags"></i></div>
                <div>
                    <p class="section-kicker">Offers & deals</p>
                    <h3>{{ $deal->title }}</h3>
                    <p>{{ __('Update timing, artwork, discount messaging, and attached products.') }}</p>
                </div>
            </div>

            @include('admin.deals._form', [
                'mode' => 'edit',
                'action' => route('admin.deals.update', $deal),
                'submitText' => __('Update Deal'),
            ])
        </section>
    </div>
</x-app-layout>
