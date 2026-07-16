@props([
    'name',
    'label' => 'Image',
    'value' => null,
    'id' => null,
    'accept' => 'image/*',
    'help' => 'PNG, JPG, GIF or SVG - up to 2MB',
    'folder' => null,
    'mediaPicker' => true,
])

@php
    $inputId = $id ?? $name;
    $mediaInput = $name . '_media';
    $mediaEnabled = $mediaPicker && filled($folder);
    $pickerUrl = $mediaEnabled ? route('admin.media.picker', ['folder' => $folder]) : null;
    $storeUrl = $mediaEnabled ? route('admin.media.store') : null;
    $existingPath = $value;

    if ($value && $folder && ! str_starts_with($value, 'uploads/') && ! str_starts_with($value, 'http://') && ! str_starts_with($value, 'https://')) {
        $existingPath = \App\Helpers\ImageManager::path($value, $folder);
    }

    $existing = $existingPath
        ? (str_starts_with($existingPath, 'http://') || str_starts_with($existingPath, 'https://') ? $existingPath : asset($existingPath))
        : null;
@endphp

<div {{ $attributes->merge(['class' => 'form-field image-upload-field media-field']) }}
    x-data="{
        existing: @js($existing),
        preview: @js($existing),
        selectedMedia: @js(old($mediaInput, '')),
        filename: '',
        modalOpen: false,
        tab: 'library',
        assets: [],
        mediaLoaded: false,
        mediaLoading: false,
        mediaSearch: '',
        uploadFiles: [],
        uploadFileName: '',
        uploadPreview: null,
        uploadAlt: '',
        uploadLoading: false,
        uploadError: '',
        recentlyUploaded: [],
        unsupported: false,
        maxUploadSize: 4 * 1024 * 1024,
        pickerUrl: @js($pickerUrl),
        storeUrl: @js($storeUrl),
        help: @js($help),
        openModal() {
            @if ($mediaEnabled)
                this.modalOpen = true;
                this.tab = 'library';
                this.loadMedia();
            @else
                this.$refs.input.click();
            @endif
        },
        pickDirect(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.filename = file.name;
            this.selectedMedia = '';
            this.unsupported = false;
            this.preview = URL.createObjectURL(file);
        },
        onError() {
            this.preview = this.existing;
            this.unsupported = true;
        },
        async loadMedia() {
            if (!this.pickerUrl || this.mediaLoaded || this.mediaLoading) return;
            this.mediaLoading = true;
            try {
                const res = await fetch(this.pickerUrl, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.assets = json.data || [];
                this.mediaLoaded = true;
            } finally {
                this.mediaLoading = false;
            }
        },
        get filteredAssets() {
            const q = this.mediaSearch.trim().toLowerCase();
            if (!q) return this.assets;
            return this.assets.filter((asset) => (asset.name || '').toLowerCase().includes(q) || (asset.filename || '').toLowerCase().includes(q));
        },
        selectMedia(asset) {
            this.selectedMedia = asset.filename;
            this.preview = asset.url;
            this.filename = '';
            this.unsupported = false;
            this.modalOpen = false;
            if (this.$refs.input) this.$refs.input.value = '';
        },
        clearSelection() {
            this.selectedMedia = '';
            this.filename = '';
            this.unsupported = false;
            this.preview = this.existing;
            if (this.$refs.input) this.$refs.input.value = '';
        },
        pickUpload(e) {
            const files = Array.from(e.target.files || []).filter((file) => file.type.startsWith('image/'));
            if (!files.length) return;
            const oversized = files.find((file) => file.size > this.maxUploadSize);
            if (oversized) {
                this.uploadFiles = [];
                this.uploadFileName = '';
                this.uploadPreview = null;
                this.uploadError = `${oversized.name} is larger than 4MB. Please choose a smaller image.`;
                if (this.$refs.uploadInput) this.$refs.uploadInput.value = '';
                return;
            }
            this.uploadFiles = files;
            this.uploadFileName = files.length === 1 ? files[0].name : `${files.length} images selected`;
            this.uploadPreview = URL.createObjectURL(files[0]);
            this.uploadError = '';
        },
        async parseUploadResponse(res) {
            const contentType = res.headers.get('content-type') || '';
            const body = await res.text();

            if (contentType.includes('application/json')) {
                return body ? JSON.parse(body) : {};
            }

            if (res.status === 419) {
                throw new Error('Your session expired. Please refresh the page and try again.');
            }

            if (res.status === 413) {
                throw new Error('The selected image is too large for the server upload limit.');
            }

            if (res.redirected || body.toLowerCase().includes('<!doctype')) {
                throw new Error('Upload failed because the server returned an HTML page. Please refresh and try again.');
            }

            throw new Error(body || 'Upload failed.');
        },
        async uploadToLibrary() {
            if (!this.uploadFiles.length || !this.storeUrl) return;
            this.uploadLoading = true;
            this.uploadError = '';

            const fd = new FormData();
            fd.append('folder', @js($folder));
            fd.append('alt_text', this.uploadAlt || '');
            this.uploadFiles.forEach((file) => fd.append('files[]', file));

            try {
                const res = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    },
                    body: fd,
                });
                const json = await this.parseUploadResponse(res);
                if (!res.ok) {
                    const firstError = json.errors ? Object.values(json.errors).flat()[0] : null;
                    throw new Error(firstError || json.message || 'Upload failed.');
                }
                const uploadedAssets = json.data || [];
                if (uploadedAssets.length) {
                    const uploadedNames = uploadedAssets.map((asset) => asset.filename);
                    this.assets = [...uploadedAssets, ...this.assets.filter((asset) => !uploadedNames.includes(asset.filename))];
                    this.recentlyUploaded = uploadedNames;
                    this.mediaLoaded = true;
                    this.uploadFiles = [];
                    this.uploadFileName = '';
                    this.uploadPreview = null;
                    this.uploadAlt = '';
                    if (this.$refs.uploadInput) this.$refs.uploadInput.value = '';
                    this.mediaSearch = '';
                    this.tab = 'library';
                }
            } catch (error) {
                this.uploadError = error.message || 'Upload failed.';
            } finally {
                this.uploadLoading = false;
            }
        }
    }">
    <label for="{{ $inputId }}">{{ $label }}</label>

    <input type="file" name="{{ $name }}" id="{{ $inputId }}" accept="{{ $accept }}"
        x-ref="input" @change="pickDirect($event)" class="hidden" @if ($mediaEnabled) tabindex="-1" @endif>
    @if ($mediaEnabled)
        <input type="hidden" name="{{ $mediaInput }}" x-model="selectedMedia">
    @endif

    <div class="media-field-card-wrap">
        <div class="media-field-card" role="button" tabindex="0" @click="openModal()" @keydown.enter.prevent="openModal()" @keydown.space.prevent="openModal()">
            <span class="media-field-card__preview">
                <template x-if="preview">
                    <img :src="preview" x-on:error="onError()" alt="Preview">
                </template>
                <template x-if="!preview">
                    <span class="media-field-card__placeholder">
                        <i class="fa-regular fa-image" x-show="!unsupported"></i>
                        <i class="fa-regular fa-file-image" x-show="unsupported" x-cloak></i>
                    </span>
                </template>
                <button type="button" class="media-field-card__clear" x-show="preview || selectedMedia || filename" x-cloak
                    @click.stop="clearSelection()" aria-label="Clear image">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </span>
            <span class="media-field-card__body">
                <strong x-text="preview ? 'Image selected' : 'Select image'"></strong>
                <small x-text="unsupported ? (filename + ' - preview not supported') : (filename || (selectedMedia ? 'Selected from media library' : help))"></small>
            </span>
            <span class="media-field-card__action">
                <i class="fa-solid fa-photo-film"></i>
                <span>{{ $mediaEnabled ? 'Open media' : 'Upload' }}</span>
            </span>
        </div>
    </div>

    @if ($mediaEnabled)
        <template x-teleport="body">
            <div class="modal-backdrop-premium media-field-modal-backdrop" x-show="modalOpen" x-cloak style="display:none;"
                x-transition:enter="media-field-backdrop-enter"
                x-transition:enter-start="media-field-backdrop-from"
                x-transition:enter-end="media-field-backdrop-to"
                x-transition:leave="media-field-backdrop-leave"
                x-transition:leave-start="media-field-backdrop-to"
                x-transition:leave-end="media-field-backdrop-from"
                @keydown.escape.window="modalOpen = false" @click.self="modalOpen = false">
                <div class="form-modal media-field-modal"
                    x-transition:enter="media-field-panel-enter"
                    x-transition:enter-start="media-field-panel-from"
                    x-transition:enter-end="media-field-panel-to"
                    x-transition:leave="media-field-panel-leave"
                    x-transition:leave-start="media-field-panel-to"
                    x-transition:leave-end="media-field-panel-from">
                    <div class="form-modal__head">
                        <div class="form-modal__icon"><i class="fa-solid fa-photo-film"></i></div>
                        <div class="flex-grow-1">
                            <h3>{{ $label }}</h3>
                            <p>Choose from the media library or upload a new reusable image.</p>
                        </div>
                        <button type="button" class="form-modal__close" @click="modalOpen = false" aria-label="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="media-field-modal__tabs" :class="{ 'is-upload': tab === 'upload' }">
                        <button type="button" :class="{ 'is-active': tab === 'library' }" @click="tab = 'library'; loadMedia()">
                            <i class="fa-solid fa-images"></i><span>Library</span>
                        </button>
                        <button type="button" :class="{ 'is-active': tab === 'upload' }" @click="tab = 'upload'">
                            <i class="fa-solid fa-cloud-arrow-up"></i><span>Upload new</span>
                        </button>
                    </div>

                    <div class="form-modal__body media-field-modal__body">
                        <div x-show="tab === 'library'" x-cloak
                            x-transition:enter="media-field-tab-enter"
                            x-transition:enter-start="media-field-tab-from"
                            x-transition:enter-end="media-field-tab-to"
                            x-transition:leave="media-field-tab-leave"
                            x-transition:leave-start="media-field-tab-to"
                            x-transition:leave-end="media-field-tab-from">
                            <div class="media-field-search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" x-model="mediaSearch"
                                    :placeholder="recentlyUploaded.length ? 'Uploaded images are shown first' : 'Search images in this folder'">
                            </div>

                            <template x-if="recentlyUploaded.length">
                                <div class="media-field-success">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span x-text="`${recentlyUploaded.length} image${recentlyUploaded.length === 1 ? '' : 's'} uploaded. Choose the image you want to use.`"></span>
                                </div>
                            </template>

                            <div class="media-field-grid">
                                <template x-if="mediaLoading">
                                    <div class="media-field-empty">Loading media...</div>
                                </template>
                                <template x-if="!mediaLoading && filteredAssets.length === 0">
                                    <div class="media-field-empty">
                                        <span class="media-field-empty__icon">
                                            <i class="fa-regular fa-image"></i>
                                        </span>
                                        <strong>No images in this folder</strong>
                                        <span class="media-field-empty__copy">Build your product media library by uploading a clean image now.</span>
                                        <button type="button" @click="tab = 'upload'">
                                            <i class="fa-solid fa-cloud-arrow-up"></i>
                                            <span>Upload image</span>
                                        </button>
                                    </div>
                                </template>
                                <template x-for="asset in filteredAssets" :key="asset.id">
                                    <button type="button" class="media-field-option"
                                        :class="{ 'is-selected': selectedMedia === asset.filename, 'is-new': recentlyUploaded.includes(asset.filename) }"
                                        @click="selectMedia(asset)">
                                        <img :src="asset.url" :alt="asset.name">
                                        <em x-show="recentlyUploaded.includes(asset.filename)" x-cloak>New</em>
                                        <span>
                                            <strong x-text="asset.name"></strong>
                                            <small x-text="[asset.size, asset.dimensions].filter(Boolean).join(' - ')"></small>
                                        </span>
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-show="tab === 'upload'" x-cloak
                            x-transition:enter="media-field-tab-enter"
                            x-transition:enter-start="media-field-tab-from"
                            x-transition:enter-end="media-field-tab-to"
                            x-transition:leave="media-field-tab-leave"
                            x-transition:leave-start="media-field-tab-to"
                            x-transition:leave-end="media-field-tab-from">
                            <div class="media-field-upload-layout">
                                <label class="media-field-dropzone" for="{{ $inputId }}_library_upload">
                                    <template x-if="uploadPreview">
                                        <img :src="uploadPreview" alt="Upload preview">
                                    </template>
                                    <template x-if="!uploadPreview">
                                        <span>
                                            <i class="fa-solid fa-cloud-arrow-up"></i>
                                            <strong>Choose an image</strong>
                                            <small>{{ $help }}</small>
                                        </span>
                                    </template>
                                </label>
                                <input id="{{ $inputId }}_library_upload" x-ref="uploadInput" type="file" accept="{{ $accept }}"
                                    class="visually-hidden" multiple @change="pickUpload($event)">

                                <div class="media-field-upload-side">
                                    <div class="form-field">
                                        <label>Alt text</label>
                                        <input type="text" class="form-input" x-model="uploadAlt" placeholder="Short image description">
                                    </div>
                                    <p class="media-field-upload-name" x-text="uploadFileName || 'No file selected'"></p>
                                    <p class="text-red-500 text-sm mt-1.5" x-show="uploadError" x-text="uploadError" x-cloak></p>
                                    <button type="button" class="form-submit-button" @click="uploadToLibrary()" :disabled="!uploadFiles.length || uploadLoading">
                                        <i class="fa-solid fa-upload"></i>
                                        <span x-text="uploadLoading ? 'Uploading...' : 'Upload to library'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif

    @error($name)
        <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
    @enderror
    @error($mediaInput)
        <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
    @enderror
</div>
