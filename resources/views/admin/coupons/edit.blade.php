<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Marketing') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Edit Coupon') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">

        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Coupon setup') }}</p>
                <h3>{{ __('Edit Coupon') }}: {{ $coupon->code }}</h3>
            </div>
            <a href="{{ route('admin.coupons.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i>
                <span>{{ __('Back') }}</span>
            </a>
        </div>

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <p class="section-kicker">{{ __('Update discount coupon') }}</p>
                    <h3>{{ __('Coupon details') }}</h3>
                    <p>{{ __('Modify the coupon discount, limits and validity window.') }}</p>
                </div>
            </div>

            @include('admin.coupons._form', [
                'mode' => 'edit',
                'action' => route('admin.coupons.update', $coupon->id),
                'submitText' => __('Update Coupon'),
            ])
        </section>
    </div>
</x-app-layout>
