<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Product Management') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Edit Color') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        
        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('Color setup') }}</p>
                <h3>Edit Color: {{ $color->name }}</h3>
            </div>
            <a href="{{ route('admin.colors.index') }}" class="ghost-button ghost-button--panel">
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
                    <p class="section-kicker">{{ __('Update product color') }}</p>
                    <h3>{{ __('Color details') }}</h3>
                    <p>{{ __('Modify the color details and configurations.') }}</p>
                </div>
            </div>

            @include('admin.colors._form', [
                'mode' => 'edit',
                'action' => route('admin.colors.update', $color->id),
                'submitText' => __('Update Color'),
            ])
        </section>
    </div>
</x-app-layout>