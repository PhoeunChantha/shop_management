<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Product Management') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Edit Size') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Size setup') }}</p>
                <h3>Edit Size: {{ $size->name }}</h3>
            </div>
            <a href="{{ route('admin.sizes.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i>
                <span>{{ __('Back') }}</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <p class="section-kicker">{{ __('Update product size') }}</p>
                    <h3>{{ __('Size details') }}</h3>
                    <p>{{ __('Modify the size details and configurations.') }}</p>
                </div>
            </div>

            @include('admin.sizes._form', [
                'mode' => 'edit',
                'action' => route('admin.sizes.update', $size->id),
                'submitText' => __('Update Size'),
            ])
        </section>
    </div>
</x-app-layout>