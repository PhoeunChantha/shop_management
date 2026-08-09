<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Content') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Edit FAQ') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('FAQ setup') }}</p>
                <h3>{{ __('Edit FAQ') }}</h3>
            </div>
            <a href="{{ route('admin.faqs.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i><span>{{ __('Back') }}</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-circle-question"></i></div>
                <div>
                    <p class="section-kicker">{{ __('Question &amp; answer') }}</p>
                    <h3>{{ __('FAQ details') }}</h3>
                    <p>{{ __('Update this FAQ entry.') }}</p>
                </div>
            </div>

            @include('admin.faqs._form', [
                'mode' => 'edit',
                'faq' => $faq,
                'action' => route('admin.faqs.update', $faq->id),
                'submitText' => __('Update FAQ'),
            ])
        </section>
    </div>
</x-app-layout>
