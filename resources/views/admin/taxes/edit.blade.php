<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Configuration</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Edit Tax Rule') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Tax setup</p>
                <h3>Edit Tax Rule</h3>
            </div>
            <a href="{{ route('admin.taxes.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>Back</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-percent"></i></div>
                <div>
                    <p class="section-kicker">Tax rate</p>
                    <h3>Tax rule details</h3>
                    <p>Update this tax rule.</p>
                </div>
            </div>

            @include('admin.taxes._form', [
                'mode' => 'edit',
                'rule' => $rule,
                'action' => route('admin.taxes.update', $rule->id),
                'submitText' => __('Update Tax Rule'),
            ])
        </section>
    </div>
</x-app-layout>
