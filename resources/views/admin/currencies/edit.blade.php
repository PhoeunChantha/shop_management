<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Configuration') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Edit Currency') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Currencies') }}</p>
                <h3>{{ __('Edit Currency') }} · {{ $currency->code }}</h3>
            </div>
            <a href="{{ route('admin.currencies.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>{{ __('Back') }}</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-money-bill-transfer"></i></div>
                <div>
                    <p class="section-kicker">{{ __('Display currency') }}</p>
                    <h3>{{ __('Currency details') }}</h3>
                    <p>{{ __('Shoppers can browse prices in this currency. Orders are still charged in :base.', ['base' => $base['code']]) }}</p>
                </div>
            </div>

            @include('admin.currencies._form', [
                'mode' => 'edit',
                'currency' => $currency,
                'action' => route('admin.currencies.update', $currency->id),
                'submitText' => __('Update Currency'),
            ])
        </section>
    </div>
</x-app-layout>
