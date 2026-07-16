<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Marketing</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Media Library') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page media-library-page" x-data="{
        uploadOpen: false,
        previewOpen: false,
        deleteConfirmOpen: false,
        previewAsset: null,
        deleteAsset: null,
        uploadDragging: false,
        uploadFileNames: [],
        copiedLabel: '',
        openPreview(asset) {
            this.previewAsset = asset;
            this.previewOpen = true;
        },
        openDelete(asset) {
            this.deleteAsset = asset;
            this.deleteConfirmOpen = true;
        },
        closeDelete() {
            this.deleteConfirmOpen = false;
            this.deleteAsset = null;
        },
        setUploadFiles(files) {
            this.uploadFileNames = Array.from(files || []).map((file) => file.name);
        },
        dropUpload(event) {
            const input = this.$refs.mediaFiles;
            if (!input) return;
            input.files = event.dataTransfer.files;
            this.setUploadFiles(input.files);
        },
        async copyText(text, label = 'Copied') {
            if (!text) return;

            try {
                await navigator.clipboard.writeText(text);
                this.copiedLabel = label;
                setTimeout(() => this.copiedLabel = '', 1200);
            } catch (error) {
                window.prompt('Copy media text', text);
            }
        }
    }">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Asset manager</p>
                <h3>Media Library</h3>
            </div>
            <div class="media-page-actions">
                <form method="POST" action="{{ route('admin.media.optimize-pending') }}" class="mb-0">
                    @csrf
                    <button type="submit" class="ghost-button media-optimize-action">
                        <i class="fa-solid fa-wand-magic-sparkles"></i><span>Optimize pending</span>
                    </button>
                </form>
                <button type="button" class="premium-button premium-button--dark" @click="uploadOpen = true">
                    <i class="fa-solid fa-cloud-arrow-up"></i><span>Upload Media</span>
                </button>
            </div>
        </div>

        <div class="media-stat-strip">
            <div class="media-stat">
                <span>Total assets</span>
                <strong>{{ number_format($totalAssets) }}</strong>
            </div>
            <div class="media-stat">
                <span>Storage used</span>
                <strong>
                    @if ($totalSize >= 1048576)
                        {{ number_format($totalSize / 1048576, 1) }} MB
                    @else
                        {{ number_format(max(1, $totalSize) / 1024, 0) }} KB
                    @endif
                </strong>
            </div>
            <div class="media-stat media-stat--muted">
                <span>Current view</span>
                <strong>{{ number_format($assets->count()) }}</strong>
            </div>
            <div class="media-stat media-stat--success">
                <span>Optimized</span>
                <strong>{{ number_format($optimizedAssets) }}</strong>
            </div>
        </div>

        <x-filter-card :action="route('admin.media.index')" class="mt-3 media-filter-card"
            :grid="'media-filter-grid'">
            <x-slot:hidden>
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            </x-slot:hidden>

            <x-select name="folder" size="sm" :options="$folders" :value="request('folder')" placeholder="All folders" />

            <div class="form-field md:col-span-2">
                <label for="media_search">Search media</label>
                <input id="media_search" type="search" name="search" value="{{ request('search') }}"
                    class="form-input" placeholder="Search filename, original name or alt text">
            </div>
        </x-filter-card>

        <section class="premium-card mt-3 media-library-card">
            <x-table-toolbar>
                <x-slot:left>
                    <x-per-page-selector :current="$perPage" :options="[12, 24, 48, 96]" />
                </x-slot:left>
                <x-slot:right>
                    <span class="media-toolbar-note" x-show="!copiedLabel">
                        @if ($totalSaved >= 1048576)
                            {{ number_format($totalSaved / 1048576, 1) }} MB saved by optimization.
                        @elseif ($totalSaved > 0)
                            {{ number_format($totalSaved / 1024, 0) }} KB saved by optimization.
                        @else
                            Use the asset URL for banners, products, brands, and content images.
                        @endif
                    </span>
                    <span class="media-toolbar-note media-toolbar-note--success" x-show="copiedLabel" x-text="copiedLabel" x-cloak></span>
                </x-slot:right>
            </x-table-toolbar>

            @if ($assets->count())
                <div class="media-grid">
                    @foreach ($assets as $asset)
                        @php($usage = $usageMap[$asset->id] ?? ['items' => [], 'count' => 0, 'label' => 'Unused'])
                        <article class="media-card">
                            <button type="button" class="media-card__preview"
                                @click="openPreview({
                                    name: @js($asset->original_name ?: $asset->filename),
                                    filename: @js($asset->filename),
                                    url: @js($asset->url),
                                    thumbnailUrl: @js($asset->thumbnail_url),
                                    path: @js($asset->path),
                                    folder: @js($folders[$asset->folder] ?? $asset->folder),
                                    size: @js($asset->size_for_humans),
                                    originalSize: @js($asset->original_size_for_humans),
                                    optimizedSize: @js($asset->optimized_size_for_humans),
                                    optimization: @js($asset->optimization_label),
                                    optimizationStatus: @js($asset->optimization_status),
                                    optimizationNotes: @js($asset->optimization_notes ?: ''),
                                    dimensions: @js($asset->width && $asset->height ? $asset->width.'x'.$asset->height : 'Unknown'),
                                    alt: @js($asset->alt_text ?: ''),
                                    uploaded: @js(optional($asset->created_at)->format('M d, Y H:i')),
                                    usage: @js($usage),
                                })">
                                @if ($asset->thumbnail_url)
                                    <img src="{{ $asset->thumbnail_url }}" alt="{{ $asset->alt_text ?: $asset->original_name ?: $asset->filename }}">
                                @else
                                    <span><i class="fa-regular fa-image"></i></span>
                                @endif
                                <span class="media-card__preview-action">
                                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                                    <b>Preview</b>
                                </span>
                            </button>
                            <div class="media-card__body">
                                <div class="media-card__title" title="{{ $asset->original_name ?: $asset->filename }}">
                                    {{ $asset->original_name ?: $asset->filename }}
                                </div>
                                <div class="media-card__meta">
                                    <span>{{ $folders[$asset->folder] ?? $asset->folder }}</span>
                                    <span>{{ $asset->size_for_humans }}</span>
                                    @if ($asset->width && $asset->height)
                                        <span>{{ $asset->width }}x{{ $asset->height }}</span>
                                    @endif
                                    <span class="media-opt-badge media-opt-badge--{{ $asset->optimization_status }}">
                                        {{ $asset->optimization_label }}
                                    </span>
                                    <span class="{{ $usage['count'] > 0 ? 'media-use-badge media-use-badge--used' : 'media-use-badge' }}">
                                        {{ $usage['label'] }}
                                    </span>
                                </div>
                                <div class="media-card__path" title="{{ $asset->path }}">{{ $asset->path }}</div>
                                <div class="media-card__actions">
                                    <button type="button" class="ghost-button media-copy-btn" @click="copyText(@js($asset->url), 'URL copied')">
                                        <i class="fa-regular fa-copy"></i><span>Copy URL</span>
                                    </button>
                                    <button type="button" class="ghost-button media-copy-btn" @click="copyText(@js($asset->path), 'Path copied')">
                                        <i class="fa-solid fa-link"></i><span>Path</span>
                                    </button>
                                    @if ($usage['count'] > 0)
                                        <button type="button" class="ghost-button media-delete-btn is-disabled"
                                            title="This media is still used and cannot be deleted">
                                            <i class="fa-solid fa-lock"></i><span>Used</span>
                                        </button>
                                    @else
                                        <button type="button" class="ghost-button media-delete-btn"
                                            @click="openDelete({
                                                name: @js($asset->original_name ?: $asset->filename),
                                                url: @js($asset->thumbnail_url ?: $asset->url),
                                                path: @js($asset->path),
                                                size: @js($asset->size_for_humans),
                                                dimensions: @js($asset->width && $asset->height ? $asset->width.'x'.$asset->height : 'Unknown'),
                                                action: @js(route('admin.media.destroy', $asset)),
                                            })">
                                            <i class="fa-solid fa-trash"></i><span>Delete</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <x-admin.empty-state class="my-4" icon="fa-solid fa-photo-film" title="No media files found"
                    message="Upload reusable product, banner, brand, collection, or content images." />
            @endif

            <x-table-footer :paginator="$assets" label="media files" />
        </section>

        <div class="modal-backdrop-premium media-delete-backdrop" x-show="deleteConfirmOpen" x-cloak style="display:none;"
            x-transition:enter="media-field-backdrop-enter"
            x-transition:enter-start="media-field-backdrop-from"
            x-transition:enter-end="media-field-backdrop-to"
            x-transition:leave="media-field-backdrop-leave"
            x-transition:leave-start="media-field-backdrop-to"
            x-transition:leave-end="media-field-backdrop-from"
            @keydown.escape.window="closeDelete()" @click.self="closeDelete()">
            <div class="form-modal media-delete-modal"
                x-transition:enter="media-field-panel-enter"
                x-transition:enter-start="media-field-panel-from"
                x-transition:enter-end="media-field-panel-to"
                x-transition:leave="media-field-panel-leave"
                x-transition:leave-start="media-field-panel-to"
                x-transition:leave-end="media-field-panel-from">
                <div class="media-delete-modal__visual">
                    <template x-if="deleteAsset?.url">
                        <img :src="deleteAsset.url" :alt="deleteAsset.name">
                    </template>
                    <span class="media-delete-modal__mark">
                        <i class="fa-solid fa-trash-can"></i>
                    </span>
                </div>
                <div class="media-delete-modal__body">
                    <div class="form-modal__head">
                        <div class="form-modal__icon media-delete-modal__icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <h3>Delete media file?</h3>
                            <p>This unused image will be permanently removed from the media library.</p>
                        </div>
                        <button type="button" class="form-modal__close" @click="closeDelete()" aria-label="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="media-delete-summary">
                        <strong x-text="deleteAsset?.name || 'Selected media'"></strong>
                        <span x-text="deleteAsset?.path || ''"></span>
                        <div>
                            <em x-text="deleteAsset?.size || 'Unknown size'"></em>
                            <em x-text="deleteAsset?.dimensions || 'Unknown dimensions'"></em>
                        </div>
                    </div>
                    <form method="POST" :action="deleteAsset?.action || '#'" class="media-delete-modal__actions">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="modal-cancel" @click="closeDelete()">Cancel</button>
                        <button type="submit" class="form-submit-button media-delete-confirm">
                            <i class="fa-solid fa-trash-can"></i><span>Delete image</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal-backdrop-premium" x-show="uploadOpen" x-cloak style="display:none;"
            @keydown.escape.window="uploadOpen = false" @click.self="uploadOpen = false">
            <div class="form-modal media-upload-modal">
                <div class="form-modal__head">
                    <div class="form-modal__icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                    <div class="flex-grow-1">
                        <h3>Upload Media</h3>
                        <p>Add reusable images to a named library folder.</p>
                    </div>
                    <button type="button" class="form-modal__close" @click="uploadOpen = false" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="form-modal__body">
                    @csrf
                    <div class="form-grid">
                        <x-select name="folder" label="Folder" :options="$folders" :value="old('folder', 'media')" required />
                        <div class="form-field">
                            <label for="alt_text">Alt text</label>
                            <input id="alt_text" name="alt_text" value="{{ old('alt_text') }}" class="form-input"
                                placeholder="Short image description">
                            @error('alt_text')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-field sm:col-span-2">
                            <label for="media_files">Images <span class="text-red-500">*</span></label>
                            <label for="media_files" class="media-dropzone" :class="{ 'is-dragging': uploadDragging, 'has-files': uploadFileNames.length }"
                                @dragover.prevent="uploadDragging = true"
                                @dragleave.prevent="uploadDragging = false"
                                @drop.prevent="uploadDragging = false; dropUpload($event)">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span x-text="uploadFileNames.length ? `${uploadFileNames.length} file${uploadFileNames.length === 1 ? '' : 's'} selected` : 'Choose or drop images'"></span>
                                <small x-text="uploadFileNames.length ? uploadFileNames.slice(0, 3).join(', ') + (uploadFileNames.length > 3 ? ' +' + (uploadFileNames.length - 3) + ' more' : '') : 'JPG, PNG, WebP, SVG or GIF. Up to 12 files, 4MB each.'"></small>
                            </label>
                            <input id="media_files" x-ref="mediaFiles" type="file" name="files[]" accept="image/*" multiple class="visually-hidden" required
                                @change="setUploadFiles($event.target.files)">
                            @error('files')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                            @error('files.*')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="form-modal__foot">
                        <button type="button" class="modal-cancel" @click="uploadOpen = false">Cancel</button>
                        <button type="submit" class="form-submit-button">
                            <i class="fa-solid fa-upload"></i><span>Upload files</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-backdrop-premium media-detail-backdrop" x-show="previewOpen" x-cloak style="display:none;"
            x-transition:enter="media-field-backdrop-enter"
            x-transition:enter-start="media-field-backdrop-from"
            x-transition:enter-end="media-field-backdrop-to"
            x-transition:leave="media-field-backdrop-leave"
            x-transition:leave-start="media-field-backdrop-to"
            x-transition:leave-end="media-field-backdrop-from"
            @keydown.escape.window="previewOpen = false" @click.self="previewOpen = false">
            <div class="form-modal media-detail-modal"
                x-transition:enter="media-field-panel-enter"
                x-transition:enter-start="media-field-panel-from"
                x-transition:enter-end="media-field-panel-to"
                x-transition:leave="media-field-panel-leave"
                x-transition:leave-start="media-field-panel-to"
                x-transition:leave-end="media-field-panel-from">
                <div class="media-detail-modal__image">
                    <template x-if="previewAsset?.url">
                        <img :src="previewAsset.url" :alt="previewAsset.alt || previewAsset.name">
                    </template>
                </div>
                <div class="media-detail-modal__body">
                    <div class="form-modal__head">
                        <div class="form-modal__icon"><i class="fa-solid fa-photo-film"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <h3 x-text="previewAsset?.name || 'Media preview'"></h3>
                            <p x-text="previewAsset?.path || ''"></p>
                        </div>
                        <button type="button" class="form-modal__close" @click="previewOpen = false" aria-label="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="media-detail-list">
                        <span><b>Folder</b><em x-text="previewAsset?.folder"></em></span>
                        <span><b>Size</b><em x-text="previewAsset?.size"></em></span>
                        <span><b>Original</b><em x-text="previewAsset?.originalSize"></em></span>
                        <span><b>Optimization</b><em x-text="previewAsset?.optimization"></em></span>
                        <span><b>Dimensions</b><em x-text="previewAsset?.dimensions"></em></span>
                        <span><b>Uploaded</b><em x-text="previewAsset?.uploaded || 'Unknown'"></em></span>
                    </div>
                    <div class="media-detail-optimization" :class="`is-${previewAsset?.optimizationStatus || 'pending'}`">
                        <strong x-text="previewAsset?.optimization || 'Pending optimization'"></strong>
                        <p x-text="previewAsset?.optimizationNotes || 'The media library keeps optimization details for future cleanup and performance checks.'"></p>
                    </div>
                    <div class="media-detail-usage" :class="{ 'is-used': (previewAsset?.usage?.count || 0) > 0 }">
                        <strong x-text="previewAsset?.usage?.count ? 'Protected media' : 'Unused media'"></strong>
                        <p x-text="previewAsset?.usage?.count ? 'This file cannot be deleted until these references are removed.' : 'This file is not linked to catalog or content records.'"></p>
                        <template x-if="previewAsset?.usage?.items?.length">
                            <div>
                                <template x-for="item in previewAsset.usage.items" :key="item.label">
                                    <span><b x-text="item.label"></b><em x-text="item.count"></em></span>
                                </template>
                            </div>
                        </template>
                    </div>
                    <div class="media-detail-actions">
                        <button type="button" class="form-submit-button" @click="copyText(previewAsset?.url, 'URL copied')">
                            <i class="fa-regular fa-copy"></i><span>Copy URL</span>
                        </button>
                        <button type="button" class="modal-cancel" @click="copyText(previewAsset?.path, 'Path copied')">
                            <i class="fa-solid fa-link"></i><span>Copy Path</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
