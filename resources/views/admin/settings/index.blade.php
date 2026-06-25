<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Configuration</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Settings') }}
            </h2>
        </div>
    </x-slot>

    @php
        // Open the tab that contains the first validation error, otherwise General.
        $activeTab = array_key_first($schema);
        foreach ($schema as $groupKey => $group) {
            foreach ($group['fields'] as $fieldKey => $field) {
                if ($errors->has($fieldKey)) {
                    $activeTab = $groupKey;
                    break 2;
                }
            }
        }
    @endphp

    <div class="" x-data="{ tab: '{{ $activeTab }}' }">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Site setup</p>
                <h3>Manage settings</h3>
            </div>
        </div>

        <x-toastr />

        <section class="premium-card form-panel settings-layout">
            <aside class="settings-tabs">
                @foreach ($schema as $groupKey => $group)
                    <button type="button" class="settings-tab" :class="{ 'is-active': tab === '{{ $groupKey }}' }"
                        @click="tab = '{{ $groupKey }}'">
                        <i class="fa-solid {{ $group['icon'] }}"></i>
                        <span>{{ $group['label'] }}</span>
                    </button>
                @endforeach
            </aside>

            <div class="settings-content">
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')

                    @foreach ($schema as $groupKey => $group)
                        <div class="form-panel-body" x-show="tab === '{{ $groupKey }}'" x-cloak>
                            @foreach ($group['fields'] as $fieldKey => $field)
                                <div class="form-field">
                                    <label for="{{ $fieldKey }}">{{ $field['label'] }}</label>

                                    @if (($field['type'] ?? 'text') === 'textarea')
                                        <textarea name="{{ $fieldKey }}" id="{{ $fieldKey }}" rows="3"
                                            class="form-input" placeholder="{{ $field['placeholder'] ?? '' }}">{{ old($fieldKey, $values[$fieldKey] ?? '') }}</textarea>
                                    @else
                                        <input type="{{ $field['type'] ?? 'text' }}" name="{{ $fieldKey }}" id="{{ $fieldKey }}"
                                            value="{{ old($fieldKey, $values[$fieldKey] ?? '') }}"
                                            class="form-input" placeholder="{{ $field['placeholder'] ?? '' }}">
                                    @endif

                                    @error($fieldKey)
                                        <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    <div class="form-panel-footer">
                        <button type="submit" class="form-submit-button">
                            <i class="fa-solid fa-check"></i>
                            {{ __('Save changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-app-layout>
