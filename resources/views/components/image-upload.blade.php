@props([
    'name',
    'label' => 'Image',
    'value' => null,
    'id' => null,
    'accept' => 'image/*',
    'help' => 'PNG, JPG, GIF or SVG — up to 2MB',
])

@php
    $inputId = $id ?? $name;
    $existing = $value ? asset($value) : null;
@endphp

<div {{ $attributes->merge(['class' => 'form-field image-upload-field']) }}
    x-data="{
        existing: @js($existing),
        preview: @js($existing),
        filename: '',
        unsupported: false,
        pick(e) {
            const file = e.target.files[0];
            if (!file) { return; }
            this.filename = file.name;
            this.unsupported = false;
            this.preview = URL.createObjectURL(file);
        },
        onError() {
            // Browser can't render this format (e.g. HEIC) — fall back gracefully.
            this.preview = this.existing;
            this.unsupported = true;
        },
        clear() {
            this.filename = '';
            this.unsupported = false;
            this.preview = this.existing;
            $refs.input.value = '';
        }
    }">
    <label for="{{ $inputId }}">{{ $label }}</label>

    <input type="file" name="{{ $name }}" id="{{ $inputId }}" accept="{{ $accept }}"
        x-ref="input" @change="pick($event)" class="hidden">

    <label for="{{ $inputId }}"
        class="flex cursor-pointer items-center gap-4 rounded-xl border border-dashed border-slate-300 bg-slate-50/60 p-3 transition hover:border-teal-400 hover:bg-white dark:border-white/15 dark:bg-white/5 dark:hover:border-teal-500/50 dark:hover:bg-white/10">

        <template x-if="preview">
            <img :src="preview" x-on:error="onError()" alt="Preview" class="h-16 w-16 flex-shrink-0 rounded-lg object-cover ring-1 ring-slate-200 dark:ring-white/10">
        </template>
        <template x-if="!preview">
            <span class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-slate-500">
                <i class="fa-regular fa-image text-xl" x-show="!unsupported"></i>
                <i class="fa-regular fa-file-image text-xl" x-show="unsupported" x-cloak></i>
            </span>
        </template>

        <span class="min-w-0 flex-1">
            <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">
                <i class="fa-solid fa-arrow-up-from-bracket mr-1.5 text-xs text-teal-600 dark:text-teal-400"></i>
                Click to upload
            </span>
            <span class="mt-0.5 block truncate text-xs"
                :class="unsupported ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400 dark:text-slate-500'"
                x-text="unsupported ? (filename + ' — preview not supported, use JPG/PNG/WebP') : (filename || @js($help))"></span>
        </span>

        <button type="button" x-show="filename" x-cloak @click.prevent="clear()"
            class="flex-shrink-0 rounded-lg px-2.5 py-1 text-xs font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10">
            <i class="fa-solid fa-xmark"></i> Remove
        </button>
    </label>

    @error($name)
        <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
    @enderror
</div>
