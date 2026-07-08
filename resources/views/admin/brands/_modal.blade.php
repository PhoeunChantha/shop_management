@php
    // Reopen the modal after a failed validation (controller redirects back here).
    $reopen = $errors->any() && old('form_mode');
@endphp

<div
    class="modal-backdrop-premium"
    data-brand-modal
    x-data="brandFormModal({
        storeUrl: @js(route('admin.brands.store')),
        reopen: {{ $reopen ? 'true' : 'false' }},
        reopenData: {
            mode: @js(old('form_mode', 'create')),
            action: @js(old('form_action')),
            name: @js(old('name', '')),
            status: @js((string) old('status', '1')),
            image: @js(old('existing_image_url') ?: null),
        },
    })"
    x-show="open"
    x-cloak
    x-transition.opacity.duration.150ms
    @keydown.escape.window="close()"
    @brand-modal.window="show($event.detail)"
    @click.self="close()"
    style="display:none;"
>
    <div class="form-modal"
        x-show="open"
        x-transition:enter="fm-enter"
        x-transition:enter-start="fm-from"
        x-transition:enter-end="fm-to"
        x-transition:leave="fm-leave"
        x-transition:leave-start="fm-to"
        x-transition:leave-end="fm-from">
        <div class="form-modal__head">
            <div class="form-modal__icon">
                <i class="fa-solid" :class="mode === 'edit' ? 'fa-pen-to-square' : 'fa-tags'"></i>
            </div>
            <div class="flex-grow-1">
                <h3 x-text="mode === 'edit' ? 'Edit Brand' : 'New Brand'"></h3>
                <p x-text="mode === 'edit' ? 'Update the brand details below.' : 'Add a new brand to organize your products.'"></p>
            </div>
            <button type="button" class="form-modal__close" @click="close()" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form :action="action" method="POST" enctype="multipart/form-data" class="form-modal__body">
            @csrf
            <input type="hidden" name="_method" :value="mode === 'edit' ? 'PUT' : 'POST'">
            <input type="hidden" name="form_mode" :value="mode">
            <input type="hidden" name="form_action" :value="action">
            <input type="hidden" name="existing_image_url" :value="existing">

            <div class="form-field">
                <label for="brand_modal_name">Brand Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="brand_modal_name" x-ref="name" x-model="name"
                    class="form-input" placeholder="e.g. Nike, Adidas, Atelier" required>
                <small class="text-gray-400 dark:text-slate-500 d-block mt-1">The URL slug is generated automatically from the name.</small>
                @error('name')
                    <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="brand_modal_status">Status</label>
                <select name="status" id="brand_modal_status" x-model="status" class="form-input">
                    <option value="1">Enable</option>
                    <option value="0">Disable</option>
                </select>
                @error('status')
                    <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label>Brand Logo</label>
                <input type="file" name="image" x-ref="file" accept="image/*" class="hidden" @change="pickFile($event)">
                <label for="" @click.prevent="$refs.file.click()"
                    class="flex cursor-pointer items-center gap-4 rounded-xl border border-dashed border-slate-300 bg-slate-50/60 p-3 transition hover:border-teal-400 hover:bg-white dark:border-white/15 dark:bg-white/5 dark:hover:border-teal-500/50 dark:hover:bg-white/10">
                    <template x-if="preview">
                        <img :src="preview" alt="Preview" class="h-16 w-16 flex-shrink-0 rounded-lg object-cover ring-1 ring-slate-200 dark:ring-white/10">
                    </template>
                    <template x-if="!preview">
                        <span class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-slate-500">
                            <i class="fa-regular fa-image text-xl"></i>
                        </span>
                    </template>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">
                            <i class="fa-solid fa-arrow-up-from-bracket mr-1.5 text-xs text-teal-600 dark:text-teal-400"></i>
                            Click to upload
                        </span>
                        <span class="mt-0.5 block truncate text-xs text-slate-400 dark:text-slate-500"
                            x-text="filename || 'PNG, JPG, GIF, SVG or WEBP — up to 2MB'"></span>
                    </span>
                    <button type="button" x-show="filename" x-cloak @click.stop.prevent="clearFile()"
                        class="flex-shrink-0 rounded-lg px-2.5 py-1 text-xs font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10">
                        <i class="fa-solid fa-xmark"></i> Remove
                    </button>
                </label>
                @error('image')
                    <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-modal__foot">
                <button type="button" class="modal-cancel" @click="close()">Cancel</button>
                <button type="submit" class="form-submit-button">
                    <i class="fa-solid fa-check"></i>
                    <span x-text="mode === 'edit' ? 'Update Brand' : 'Create Brand'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

@once
    @push('js')
        <script>
            // Trigger helper used by the "New Brand" and row "Edit" buttons.
            window.openBrandModal = function (detail) {
                window.dispatchEvent(new CustomEvent('brand-modal', { detail: detail || {} }));
            };

            window.brandFormModal = function (config) {
                return {
                    open: false,
                    mode: 'create',
                    action: config.storeUrl,
                    storeUrl: config.storeUrl,
                    name: '',
                    status: '1',
                    preview: null,
                    existing: null,
                    filename: '',

                    init() {
                        if (config.reopen) {
                            this.show(config.reopenData);
                        }
                    },

                    show(detail = {}) {
                        this.mode = detail.mode || 'create';
                        this.action = detail.action || this.storeUrl;
                        this.name = detail.name || '';
                        this.status = detail.status != null ? String(detail.status) : '1';
                        this.existing = detail.image || null;
                        this.preview = detail.image || null;
                        this.filename = '';
                        if (this.$refs.file) this.$refs.file.value = '';
                        this.open = true;
                        this.$nextTick(() => this.$refs.name && this.$refs.name.focus());
                    },

                    close() {
                        this.open = false;
                    },

                    pickFile(e) {
                        const file = e.target.files[0];
                        if (!file) return;
                        this.filename = file.name;
                        this.preview = URL.createObjectURL(file);
                    },

                    clearFile() {
                        this.filename = '';
                        this.preview = this.existing;
                        if (this.$refs.file) this.$refs.file.value = '';
                    },
                };
            };
        </script>
    @endpush
@endonce
