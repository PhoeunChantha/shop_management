@props([
    'name' => 'images',
    'existing' => null,            // Collection<ProductImage>|null
    'folder' => 'products',
    'removedName' => 'removed_images',
    'primaryName' => 'primary_image_id',
    'mediaName' => 'images_media',
    'mediaPicker' => true,
    'accept' => 'image/jpeg,image/png,image/webp,image/svg+xml',
    'maxSize' => '2MB',
])

@php($existing = $existing ?? collect())
@php($currentPrimary = optional($existing->firstWhere('is_primary', true))->id ?? optional($existing->first())->id)
@php($pickerUrl = $mediaPicker ? route('admin.media.picker', ['folder' => $folder]) : null)

<div class="gallery-upload" :class="{ 'is-dragging': dragging }"
    @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
    @drop.prevent="dragging = false; addFiles($event.dataTransfer.files)"
    x-data="{
        dt: new DataTransfer(),
        files: [],
        media: [],
        assets: [],
        mediaOpen: false,
        mediaLoaded: false,
        mediaLoading: false,
        mediaSearch: '',
        pickerUrl: @js($pickerUrl),
        removed: [],
        dragging: false,
        addFiles(list) {
            for (const f of list) {
                if (!f.type.startsWith('image/')) continue;
                if (f.size > 2 * 1024 * 1024) { alert(f.name + ' exceeds {{ $maxSize }}.'); continue; }
                this.dt.items.add(f);
            }
            this.sync();
        },
        sync() {
            this.$refs.input.files = this.dt.files;
            this.files = Array.from(this.dt.files).map((f, i) => ({ i, name: f.name, url: URL.createObjectURL(f) }));
        },
        removeNew(idx) {
            const next = new DataTransfer();
            Array.from(this.dt.files).forEach((f, i) => { if (i !== idx) next.items.add(f); });
            this.dt = next; this.sync();
        },
        async openMedia() {
            if (!this.pickerUrl) return;
            this.mediaOpen = true;
            if (this.mediaLoaded || this.mediaLoading) return;
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
        addMedia(asset) {
            if (!this.media.some((item) => item.filename === asset.filename)) this.media.push(asset);
        },
        removeMedia(filename) {
            this.media = this.media.filter((asset) => asset.filename !== filename);
        },
    }">

    <input type="file" x-ref="input" name="{{ $name }}[]" accept="{{ $accept }}" multiple class="hidden"
        @change="addFiles($event.target.files)">

    <div class="gallery-grid">
        {{-- Upload tile — always first --}}
        <button type="button" class="gallery-tile" @click="$refs.input.click()">
            <span class="gallery-tile__icon"><i class="fa-regular fa-image"></i><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
            <span class="gallery-tile__title">Click to upload</span>
            <span class="gallery-tile__hint">or drag and drop</span>
        </button>

        @if ($mediaPicker)
            <button type="button" class="gallery-tile gallery-tile--library" @click="openMedia()">
                <span class="gallery-tile__icon"><i class="fa-solid fa-photo-film"></i></span>
                <span class="gallery-tile__title">Choose from library</span>
                <span class="gallery-tile__hint">reuse existing media</span>
            </button>
        @endif

        {{-- Existing images (delete + primary picker) --}}
        @foreach ($existing as $img)
            <div class="gallery-item" x-show="!removed.includes({{ $img->id }})">
                <img src="{{ Imageurl($img->image, $folder) }}" alt="image">
                <label class="gallery-item__star" title="Set as primary" @click.stop>
                    <input type="radio" name="{{ $primaryName }}" value="{{ $img->id }}" @checked($currentPrimary === $img->id)>
                    <i class="fa-solid fa-star"></i>
                </label>
                <button type="button" class="gallery-item__x" @click.stop="removed.push({{ $img->id }})" title="Remove">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endforeach

        {{-- Newly selected files --}}
        <template x-for="f in files" :key="f.url">
            <div class="gallery-item">
                <img :src="f.url" :alt="f.name">
                <button type="button" class="gallery-item__x" @click.stop="removeNew(f.i)" title="Remove">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </template>

        {{-- Selected library images --}}
        <template x-for="asset in media" :key="asset.filename">
            <div class="gallery-item">
                <img :src="asset.url" :alt="asset.name">
                <input type="hidden" name="{{ $mediaName }}[]" :value="asset.filename">
                <button type="button" class="gallery-item__x" @click.stop="removeMedia(asset.filename)" title="Remove">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </template>
    </div>

    @if ($mediaPicker)
        <div class="media-picker-panel gallery-media-panel" x-show="mediaOpen" x-cloak @click.outside="mediaOpen = false">
            <div class="media-picker-panel__search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" x-model="mediaSearch" placeholder="Search product media">
            </div>
            <div class="media-picker-panel__body">
                <template x-if="mediaLoading">
                    <div class="media-picker-panel__empty">Loading media...</div>
                </template>
                <template x-if="!mediaLoading && filteredAssets.length === 0">
                    <div class="media-picker-panel__empty">No product media found.</div>
                </template>
                <template x-for="asset in filteredAssets" :key="asset.id">
                    <button type="button" class="media-picker-option"
                        :class="{ 'is-selected': media.some((item) => item.filename === asset.filename) }"
                        @click="addMedia(asset)">
                        <img :src="asset.url" :alt="asset.name">
                        <span>
                            <strong x-text="asset.name"></strong>
                            <small x-text="[asset.size, asset.dimensions].filter(Boolean).join(' - ')"></small>
                        </span>
                    </button>
                </template>
            </div>
        </div>
    @endif

    {{-- Hidden inputs for images marked for deletion --}}
    <template x-for="id in removed" :key="id">
        <input type="hidden" name="{{ $removedName }}[]" :value="id">
    </template>
</div>
