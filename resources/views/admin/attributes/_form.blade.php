@php
    $isEdit = ($mode ?? 'create') === 'edit';

    $valueRows = old('values');
    if ($valueRows === null && $isEdit) {
        $valueRows = $attribute->values->map(fn ($v) => [
            'id' => (string) $v->id,
            'value' => $v->value,
            'color_hex' => $v->color_hex ?? '',
        ])->values()->all();
    }
    $valueRows = $valueRows ?: [['id' => '', 'value' => '', 'color_hex' => '']];
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="form-panel-body grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="form-field col-span-2 md:col-span-1">
            <label for="name">Attribute Name <span class="text-red-500">*</span></label>
            <input value="{{ old('name', $attribute->name ?? '') }}" type="text" name="name" id="name"
                class="form-input" placeholder="e.g. Size, Color, Material, Storage" required>
            @error('name')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="form-field col-span-2 md:col-span-1">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input">
                <option value="1" {{ old('status', $attribute->status ?? 1) == 1 ? 'selected' : '' }}>Enable</option>
                <option value="0" {{ old('status', $attribute->status ?? 1) == 0 ? 'selected' : '' }}>Disable</option>
            </select>
        </div>
    </div>

    {{-- Values editor --}}
    <div class="form-panel-body pt-0"
        x-data="{
            values: @js($valueRows),
            add() { this.values.push({ id: '', value: '', color_hex: '' }); this.$nextTick(() => { const els = this.$root.querySelectorAll('[data-value-input]'); els[els.length - 1]?.focus(); }); },
            remove(i) { this.values.splice(i, 1); if (this.values.length === 0) this.add(); }
        }">
        <div class="attr-values-head">
            <label>Values <span class="text-red-500">*</span></label>
            <button type="button" class="dynamic-add-button" @click="add()"><i class="fa-solid fa-plus"></i> Add value</button>
        </div>
        <p class="form-help mb-2">Add each option — e.g. for Size: S, M, L, XL. Add a color for swatch attributes.</p>

        <div class="attr-values-list">
            <template x-for="(row, i) in values" :key="i">
                <div class="attr-value-row">
                    <input type="hidden" :name="`values[${i}][id]`" :value="row.id">
                    <input type="hidden" :name="`values[${i}][sort_order]`" :value="i">

                    <span class="attr-value-swatch" :style="row.color_hex ? `background:${row.color_hex}` : ''"
                        :class="{ 'is-empty': !row.color_hex }"></span>

                    <input type="text" class="form-input" data-value-input
                        :name="`values[${i}][value]`" x-model="row.value" placeholder="Value (e.g. Medium)">

                    <label class="attr-value-color" title="Optional color swatch">
                        <i class="fa-solid fa-eye-dropper"></i>
                        <input type="color" :value="row.color_hex || '#000000'" @input="row.color_hex = $event.target.value">
                    </label>
                    <input type="text" class="form-input attr-hex" :name="`values[${i}][color_hex]`"
                        x-model="row.color_hex" placeholder="#hex (optional)">

                    <button type="button" class="dynamic-remove-button" @click="remove(i)" aria-label="Remove value">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </template>
        </div>

        @foreach ($errors->keys() as $key)
            @if (str_starts_with($key, 'values'))
                <p class="text-red-500 text-sm mt-1.5">{{ $errors->first($key) }}</p>
            @endif
        @endforeach
    </div>

    <div class="form-panel-footer mt-4">
        <a href="{{ route('admin.attributes.index') }}" class="form-cancel-button">Cancel</a>
        <button type="submit" class="form-submit-button">
            <i class="fa-solid fa-check"></i>
            {{ $submitText }}
        </button>
    </div>
</form>
