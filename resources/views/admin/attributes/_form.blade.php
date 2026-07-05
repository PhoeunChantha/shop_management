@php
    use App\Enums\AttributeType;

    $isEdit = ($mode ?? 'create') === 'edit';
    $typeValue = old('type', $isEdit ? $attribute->type->value : 'custom');

    // Custom (free-text) value rows.
    $valueRows = old('values');
    if ($valueRows === null && $isEdit) {
        $valueRows = $attribute->values->whereNull('source_type')->map(fn ($v) => [
            'id' => (string) $v->id,
            'value' => $v->value,
            'color_hex' => $v->color_hex ?? '',
        ])->values()->all();
    }
    $valueRows = $valueRows ?: [['id' => '', 'value' => '', 'color_hex' => '']];

    // Linked selections (Size / Color) reconstructed from the values' source ids.
    $selectedSizeIds = old('size_ids');
    if ($selectedSizeIds === null && $isEdit) {
        $selectedSizeIds = $attribute->values->where('source_type', 'size')->pluck('source_id')->map(fn ($id) => (string) $id)->values()->all();
    }
    $selectedSizeIds = $selectedSizeIds ?: [];

    $selectedColorIds = old('color_ids');
    if ($selectedColorIds === null && $isEdit) {
        $selectedColorIds = $attribute->values->where('source_type', 'color')->pluck('source_id')->map(fn ($id) => (string) $id)->values()->all();
    }
    $selectedColorIds = $selectedColorIds ?: [];
@endphp

<form action="{{ $action }}" method="POST"
    x-data="{
        type: @js($typeValue),
        values: @js($valueRows),
        sizeIds: @js($selectedSizeIds),
        colorIds: @js($selectedColorIds),
        add() { this.values.push({ id: '', value: '', color_hex: '' }); },
        remove(i) { this.values.splice(i, 1); if (this.values.length === 0) this.add(); }
    }">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="form-field">
            <label for="name">Attribute Name <span class="text-red-500">*</span></label>
            <input value="{{ old('name', $attribute->name ?? '') }}" type="text" name="name" id="name"
                class="form-input" placeholder="e.g. Size, Color, Material" required>
            @error('name')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label for="type">Type <span class="text-red-500">*</span></label>
            <select name="type" id="type" class="form-input" x-model="type">
                @foreach (AttributeType::options() as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $attribute->status ?? 1) == 1 ? 'selected' : '' }}>Enable</option>
                <option value="0" {{ old('status', $attribute->status ?? 1) == 0 ? 'selected' : '' }}>Disable</option>
            </select>
        </div>
    </div>

    {{-- CUSTOM: free-text values --}}
    <div class="form-panel-body pt-0" x-show="type === 'custom'" x-cloak>
        <div class="attr-values-head">
            <label>Values <span class="text-red-500">*</span></label>
            <button type="button" class="dynamic-add-button" @click="add()"><i class="fa-solid fa-plus"></i> Add value</button>
        </div>
        <p class="form-help mb-2">Add each option — e.g. Cotton, Denim, Leather. Add a color for swatch attributes.</p>

        <div class="attr-values-list">
            <template x-for="(row, i) in values" :key="i">
                <div class="attr-value-row">
                    <input type="hidden" :name="`values[${i}][id]`" :value="row.id" :disabled="type !== 'custom'">
                    <input type="hidden" :name="`values[${i}][sort_order]`" :value="i" :disabled="type !== 'custom'">

                    <span class="attr-value-swatch" :style="row.color_hex ? `background:${row.color_hex}` : ''" :class="{ 'is-empty': !row.color_hex }"></span>

                    <input type="text" class="form-input" :name="`values[${i}][value]`" x-model="row.value"
                        placeholder="Value (e.g. Medium)" :disabled="type !== 'custom'">

                    <label class="attr-value-color" title="Optional color swatch">
                        <i class="fa-solid fa-eye-dropper"></i>
                        <input type="color" :value="row.color_hex || '#000000'" @input="row.color_hex = $event.target.value" :disabled="type !== 'custom'">
                    </label>
                    <input type="text" class="form-input attr-hex" :name="`values[${i}][color_hex]`" x-model="row.color_hex"
                        placeholder="#hex (optional)" :disabled="type !== 'custom'">

                    <button type="button" class="dynamic-remove-button" @click="remove(i)" aria-label="Remove value">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </template>
        </div>
        @error('values')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
    </div>

    {{-- SIZE: pick from managed Sizes --}}
    <div class="form-panel-body pt-0" x-show="type === 'size'" x-cloak>
        <div class="attr-values-head">
            <label>Sizes <span class="text-red-500">*</span></label>
            <a href="{{ route('admin.sizes.index') }}" class="attr-mini-btn"><i class="fa-solid fa-arrow-up-right-from-square"></i> Manage Sizes</a>
        </div>
        <p class="form-help mb-2">Select which sizes this attribute offers. Edit their names/codes on the Sizes page.</p>
        <div class="attr-source-grid">
            @forelse ($sizes as $size)
                <label class="attr-check" :class="{ 'is-on': sizeIds.includes('{{ $size->id }}') }">
                    <input type="checkbox" class="visually-hidden" name="size_ids[]" value="{{ $size->id }}"
                        x-model="sizeIds" :disabled="type !== 'size'">
                    <span class="attr-check__box"><i class="fa-solid fa-check"></i></span>
                    <span>{{ $size->name }}@if ($size->code)<span class="attr-code-tag">{{ $size->code }}</span>@endif</span>
                </label>
            @empty
                <span class="text-sm text-gray-400">No sizes yet — <a href="{{ route('admin.sizes.index') }}" class="text-blue-500 underline">create some</a>.</span>
            @endforelse
        </div>
        @error('size_ids')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
    </div>

    {{-- COLOR: pick from managed Colors --}}
    <div class="form-panel-body pt-0" x-show="type === 'color'" x-cloak>
        <div class="attr-values-head">
            <label>Colors <span class="text-red-500">*</span></label>
            <a href="{{ route('admin.colors.index') }}" class="attr-mini-btn"><i class="fa-solid fa-arrow-up-right-from-square"></i> Manage Colors</a>
        </div>
        <p class="form-help mb-2">Select which colors this attribute offers. Edit their swatches on the Colors page.</p>
        <div class="attr-source-grid">
            @forelse ($colors as $color)
                <label class="attr-check" :class="{ 'is-on': colorIds.includes('{{ $color->id }}') }">
                    <input type="checkbox" class="visually-hidden" name="color_ids[]" value="{{ $color->id }}"
                        x-model="colorIds" :disabled="type !== 'color'">
                    <span class="attr-check__box"><i class="fa-solid fa-check"></i></span>
                    @if ($color->hex_code)<span class="attr-swatch" style="background: {{ $color->hex_code }};"></span>@endif
                    <span>{{ $color->name }}</span>
                </label>
            @empty
                <span class="text-sm text-gray-400">No colors yet — <a href="{{ route('admin.colors.index') }}" class="text-blue-500 underline">create some</a>.</span>
            @endforelse
        </div>
        @error('color_ids')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
    </div>

    <div class="form-panel-footer mt-4">
        <a href="{{ route('admin.attributes.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
