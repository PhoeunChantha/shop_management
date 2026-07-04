<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Marketing</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Create Coupon') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">

        <div class="page-section-header">
            <div>
                <p class="section-kicker">Coupon setup</p>
                <h3>New Coupon</h3>
            </div>
            <a href="{{ route('admin.coupons.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <div>
                    <p class="section-kicker">Create discount coupon</p>
                    <h3>Coupon details</h3>
                    <p>Set up a promo code with its discount, limits and validity window.</p>
                </div>
            </div>

            @include('admin.coupons._form', [
                'mode' => 'create',
                'action' => route('admin.coupons.store'),
                'submitText' => __('Create Coupon'),
            ])
        </section>
    </div>
</x-app-layout>
