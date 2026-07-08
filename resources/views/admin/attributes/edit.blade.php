<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Catalog</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Edit Attribute') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page admin-form-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Attribute setup</p>
                <h3>Edit Attribute: {{ $attribute->name }}</h3>
            </div>
            <a href="{{ route('admin.attributes.index') }}" class="ghost-button ghost-button--panel">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        <x-message />

        <section class="premium-card form-panel">
            <div class="form-panel-header">
                <div class="form-panel-icon"><i class="fa-solid fa-pen-to-square"></i></div>
                <div>
                    <p class="section-kicker">Update product attribute</p>
                    <h3>Attribute details</h3>
                    <p>Modify the attribute and its values.</p>
                </div>
            </div>

            @include('admin.attributes._form', [
                'mode' => 'edit',
                'action' => route('admin.attributes.update', $attribute->id),
                'submitText' => __('Update Attribute'),
            ])
        </section>
    </div>
</x-app-layout>
