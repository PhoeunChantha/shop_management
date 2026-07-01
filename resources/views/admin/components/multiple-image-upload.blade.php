@props([
    'name' => 'images',
    'existing' => null,            // Collection<ProductImage>|null
    'folder' => 'products',
    'removedName' => 'removed_images',
    'primaryName' => 'primary_image_id',
    'accept' => 'image/jpeg,image/png,image/webp,image/svg+xml',
    'maxSize' => '2MB',
])

@php($existing = $existing ?? collect())
@php($currentPrimary = optional($existing->firstWhere('is_primary', true))->id ?? optional($existing->first())->id)

<div class="img-uploader" :class="{ 'is-dragging': dragging }"
    @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
    @drop.prevent="dragging = false; addFiles($event.dataTransfer.files)"
    x-data="{
        dt: new DataTransfer(),
        files: [],
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
        }
    }">

    <input type="file" x-ref="input" name="{{ $name }}[]" accept="{{ $accept }}" multiple class="hidden"
        @change="addFiles($event.target.files)">

    {{-- Previews live INSIDE the upload box, above the prompt --}}
    <div class="img-grid" x-show="files.length + ({{ $existing->count() }} - removed.length) > 0" x-cloak>
        {{-- Existing images (with delete + primary picker) --}}
        @foreach ($existing as $img)
            <div class="img-cell" x-show="!removed.includes({{ $img->id }})">
                <img src="{{ Imageurl($img->image, $folder) }}" alt="image">
                <label class="img-cell__primary" title="Set as primary" @click.stop>
                    <input type="radio" name="{{ $primaryName }}" value="{{ $img->id }}" @checked($currentPrimary === $img->id)>
                    <i class="fa-solid fa-star"></i>
                </label>
                <button type="button" class="img-cell__remove" @click.stop="removed.push({{ $img->id }})" title="Remove">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endforeach

        {{-- Newly selected files --}}
        <template x-for="f in files" :key="f.url">
            <div class="img-cell">
                <img :src="f.url" :alt="f.name">
                <span class="img-cell__badge">New</span>
                <button type="button" class="img-cell__remove" @click.stop="removeNew(f.i)" title="Remove">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </template>
    </div>

    {{-- Drop / browse prompt (click opens the file picker) --}}
    <button type="button" class="img-drop" @click="$refs.input.click()">
        <span class="img-dropzone__icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
        <span class="img-dropzone__title">Drag &amp; drop images here, or <u>browse</u></span>
        <span class="img-dropzone__hint">JPG, PNG, WEBP or SVG — up to {{ $maxSize }} each</span>
    </button>

    {{-- Hidden inputs for images marked for deletion --}}
    <template x-for="id in removed" :key="id">
        <input type="hidden" name="{{ $removedName }}[]" :value="id">
    </template>
</div>
