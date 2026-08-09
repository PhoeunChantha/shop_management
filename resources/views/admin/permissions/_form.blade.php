@php
    $isEdit = ($mode ?? 'create') === 'edit';
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body">
        <div class="form-field">
            @if ($isEdit)
                <label for="name">{{ __('Permission Name') }}</label>
                <input value="{{ old('name', $permission->name ?? '') }}" type="text" name="name" id="name"
                    class="form-input"
                    placeholder="{{ __('e.g. view users') }}">
                @error('name')
                    <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                @enderror
            @else
                <div class="dynamic-field-header">
                    <label>{{ __('Permission Names') }}</label>
                    <button type="button" class="dynamic-add-button" data-add-permission-input>
                        <i class="fa-solid fa-plus"></i>
                        <span>{{ __('Add') }}</span>
                    </button>
                </div>

                <div class="dynamic-input-list" data-permission-input-list>
                    @foreach (old('names', ['']) as $name)
                        <div class="dynamic-input-row">
                            <input value="{{ $name }}" type="text" name="names[]" class="form-input"
                                placeholder="{{ __('e.g. view users') }}">
                            <button type="button" class="dynamic-remove-button" data-remove-permission-input
                                aria-label="{{ __('Remove permission input') }}">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    @endforeach
                </div>

                @error('names')
                    <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                @enderror
                @error('names.*')
                    <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                @enderror
            @endif
        </div>
    </div>

    <div class="form-panel-footer">
        <a href="{{ route('admin.permissions.index') }}" class="form-cancel-button">{{ __('Cancel') }}</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
