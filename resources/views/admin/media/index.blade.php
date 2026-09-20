<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Marketing') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Media Library') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page media-library-page" x-data="{
        uploadOpen: false,
        detailOpen: false,
        deleteConfirmOpen: false,
        detailAsset: null,
        deleteAsset: null,
        uploadDragging: false,
        uploadFileNames: [],
        copiedLabel: '',
        selected: [],
        view: 'grid',
        pageIds: @js($assets->pluck('id')->all()),
        init() {
            try {
                const stored = window.localStorage.getItem('media-library-view');
                if (stored === 'grid' || stored === 'list') this.view = stored;
            } catch (error) { /* private mode — keep the default */ }
        },
        setView(mode) {
            this.view = mode;
            try { window.localStorage.setItem('media-library-view', mode); } catch (error) {}
        },
        isSelected(id) {
            return this.selected.includes(id);
        },
        toggle(id) {
            this.selected = this.isSelected(id)
                ? this.selected.filter((value) => value !== id)
                : [...this.selected, id];
        },
        get allSelected() {
            return this.pageIds.length > 0 && this.pageIds.every((id) => this.selected.includes(id));
        },
        toggleAll() {
            this.selected = this.allSelected ? [] : [...this.pageIds];
        },
        clearSelection() {
            this.selected = [];
        },
        runBulk(action, folder = '') {
            if (!this.selected.length) return;
            if (action === 'delete' && !window.confirm(@js(__('Delete the selected unused media? Files still referenced are skipped.')))) return;
            if (action === 'move' && !folder) return;

            this.$refs.bulkAction.value = action;
            this.$refs.bulkFolder.value = folder;
            this.$refs.bulkIds.innerHTML = '';
            this.selected.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                this.$refs.bulkIds.appendChild(input);
            });
            this.$refs.bulkForm.submit();
        },
        openDetail(asset) {
            this.detailAsset = asset;
            this.detailOpen = true;
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
                <p class="section-kicker">{{ __('Asset manager') }}</p>
                <h3>{{ __('Media Library') }}</h3>
            </div>
            <div class="media-page-actions">
                <span class="media-storage-chip" title="{{ __('Where new uploads are written') }}">
                    <i class="fa-solid fa-hard-drive"></i>{{ $storageLabel }}
                </span>
                @if ($pendingAssets > 0)
                    <form method="POST" action="{{ route('admin.media.optimize-pending') }}" class="mb-0">
                        @csrf
                        <button type="submit" class="ghost-button media-optimize-action">
                            <i class="fa-solid fa-wand-magic-sparkles"></i><span>{{ __('Optimize') }} {{ number_format($pendingAssets) }}</span>
                        </button>
                    </form>
                @endif
                <button type="button" class="premium-button premium-button--dark" @click="uploadOpen = true">
                    <i class="fa-solid fa-cloud-arrow-up"></i><span>{{ __('Upload Media') }}</span>
                </button>
            </div>
        </div>

        <div class="media-stat-strip">
            <div class="media-stat">
                <span>{{ __('Total assets') }}</span>
                <strong>{{ number_format($totalAssets) }}</strong>
            </div>
            <div class="media-stat">
                <span>{{ __('Storage used') }}</span>
                <strong>
                    @if ($totalSize >= 1048576)
                        {{ number_format($totalSize / 1048576, 1) }} MB
                    @else
                        {{ number_format(max(1, $totalSize) / 1024, 0) }} KB
                    @endif
                </strong>
            </div>
            <div class="media-stat media-stat--muted">
                <span>{{ __('Unused') }}</span>
                <strong>{{ number_format($unusedAssets) }}</strong>
            </div>
            <div class="media-stat media-stat--success">
                <span>{{ __('Optimized') }}</span>
                <strong>{{ number_format($optimizedAssets) }}</strong>
            </div>
        </div>

        @include('admin.saved-views._bar', ['scope' => 'media', 'icon' => 'fa-photo-film', 'color' => '#0f766e'])

        <x-filter-card :action="route('admin.media.index')" class="mt-3 media-filter-card"
            :grid="'media-filter-grid'">
            <x-slot:hidden>
                <input type="hidden" name="per_page" value="{{ $perPage }}">
            </x-slot:hidden>

            <x-select name="folder" size="sm" :label="__('Folder')" :options="$folders" :value="request('folder')" placeholder="{{ __('All folders') }}" />
            <x-select name="kind" size="sm" :label="__('File type')" :options="$kinds" :value="request('kind')" placeholder="{{ __('Any type') }}" />
            <x-select name="usage" size="sm" :label="__('Usage')" :options="$usageStates" :value="request('usage')" placeholder="{{ __('Any usage') }}" />
            <x-select name="sort" size="sm" :label="__('Sort by')" :options="$sorts" :value="request('sort')" placeholder="{{ __('Newest first') }}" />

            <div class="form-field">
                <label for="media_from">{{ __('Uploaded from') }}</label>
                <input id="media_from" type="date" name="from" value="{{ request('from') }}" class="form-input">
            </div>

            <div class="form-field">
                <label for="media_to">{{ __('Uploaded to') }}</label>
                <input id="media_to" type="date" name="to" value="{{ request('to') }}" class="form-input">
            </div>

            <div class="form-field md:col-span-2">
                <label for="media_search">{{ __('Search media') }}</label>
                <input id="media_search" type="search" name="search" value="{{ request('search') }}"
                    class="form-input" placeholder="{{ __('Search name, alt text, tags or filename') }}">
            </div>
        </x-filter-card>

        {{-- Bulk actions post here; the ids are injected by runBulk() so the
             visible grid stays a plain, non-nested list of links. --}}
        <form method="POST" action="{{ route('admin.media.bulk') }}" class="d-none" x-ref="bulkForm">
            @csrf
            <input type="hidden" name="action" x-ref="bulkAction">
            <input type="hidden" name="folder" x-ref="bulkFolder">
            <div x-ref="bulkIds"></div>
        </form>

        <section class="premium-card mt-3 media-library-card">
            <x-table-toolbar>
                <x-slot:left>
                    <x-per-page-selector :current="$perPage" :options="[12, 24, 48, 96]" />
                    <div class="media-view-toggle" role="group" aria-label="{{ __('View mode') }}">
                        <button type="button" :class="{ 'is-active': view === 'grid' }" @click="setView('grid')" aria-label="{{ __('Grid view') }}">
                            <i class="fa-solid fa-grip"></i>
                        </button>
                        <button type="button" :class="{ 'is-active': view === 'list' }" @click="setView('list')" aria-label="{{ __('List view') }}">
                            <i class="fa-solid fa-list"></i>
                        </button>
                    </div>
                </x-slot:left>
                <x-slot:right>
                    <span class="media-toolbar-note" x-show="!copiedLabel">
                        @if ($totalSaved >= 1048576)
                            {{ number_format($totalSaved / 1048576, 1) }} MB saved by optimization.
                        @elseif ($totalSaved > 0)
                            {{ number_format($totalSaved / 1024, 0) }} KB saved by optimization.
                        @else
                            {{ __('Use the asset URL for banners, products, brands, and content images.') }}
                        @endif
                    </span>
                    <span class="media-toolbar-note media-toolbar-note--success" x-show="copiedLabel" x-text="copiedLabel" x-cloak></span>
                </x-slot:right>
            </x-table-toolbar>

            @if ($assets->count())
                <div class="media-bulkbar" x-show="selected.length" x-cloak>
                    <label class="media-bulkbar__all">
                        <input type="checkbox" :checked="allSelected" @change="toggleAll()">
                        <span x-text="`${selected.length} {{ __('selected') }}`"></span>
                    </label>
                    <div class="media-bulkbar__actions">
                        <select class="form-input form-input--sm" @change="runBulk('move', $event.target.value); $event.target.value = ''">
                            <option value="">{{ __('Move to folder…') }}</option>
                            @foreach ($folders as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="ghost-button" @click="runBulk('optimize')">
                            <i class="fa-solid fa-wand-magic-sparkles"></i><span>{{ __('Optimize') }}</span>
                        </button>
                        <button type="button" class="ghost-button media-delete-btn" @click="runBulk('delete')">
                            <i class="fa-solid fa-trash"></i><span>{{ __('Delete unused') }}</span>
                        </button>
                        <button type="button" class="ghost-button" @click="clearSelection()">
                            <i class="fa-solid fa-xmark"></i><span>{{ __('Clear') }}</span>
                        </button>
                    </div>
                </div>

                <div class="media-selectall">
                    <label>
                        <input type="checkbox" :checked="allSelected" @change="toggleAll()">
                        <span>{{ __('Select all on this page') }}</span>
                    </label>
                </div>

                <div class="media-grid" :class="view === 'list' ? 'media-grid--list' : ''">
                    @foreach ($assets as $asset)
                        @php($usage = $usageMap[$asset->id] ?? ['items' => [], 'count' => 0, 'label' => 'Unused'])
                        @php($assetPayload = [
                            'id' => $asset->id,
                            'name' => $asset->display_name,
                            'title' => $asset->title ?: '',
                            'filename' => $asset->filename,
                            'url' => $asset->url,
                            'thumbnailUrl' => $asset->thumbnail_url,
                            'path' => $asset->path,
                            'folderKey' => $asset->folder,
                            'folder' => $folders[$asset->folder] ?? $asset->folder,
                            'storage' => $asset->storage_label,
                            'size' => $asset->size_for_humans,
                            'originalSize' => $asset->original_size_for_humans,
                            'optimizedSize' => $asset->optimized_size_for_humans,
                            'optimization' => $asset->optimization_label,
                            'optimizationStatus' => $asset->optimization_status,
                            'optimizationNotes' => $asset->optimization_notes ?: '',
                            'dimensions' => $asset->width && $asset->height ? $asset->width.'x'.$asset->height : 'Unknown',
                            'alt' => $asset->alt_text ?: '',
                            'tags' => collect($asset->tags ?? [])->join(', '),
                            'uploaded' => optional($asset->created_at)->format('M d, Y H:i'),
                            'updateAction' => route('admin.media.update', $asset),
                            'usage' => $usage,
                        ])
                        <article class="media-card" :class="isSelected({{ $asset->id }}) ? 'is-selected' : ''"
                            x-data="{ menu: false }" @click.outside="menu = false" @keydown.escape="menu = false">
                            <label class="media-card__select" @click.stop>
                                <input type="checkbox" :checked="isSelected({{ $asset->id }})" @change="toggle({{ $asset->id }})"
                                    aria-label="{{ __('Select') }} {{ $asset->display_name }}">
                            </label>
                            @if (! $asset->alt_text)
                                <span class="media-card__flag" title="{{ __('No alt text — add one for SEO and accessibility') }}">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                </span>
                            @endif
                            <button type="button" class="media-card__preview" @click="openDetail(@js($assetPayload))">
                                @if ($asset->thumbnail_url)
                                    <img src="{{ $asset->thumbnail_url }}" alt="{{ $asset->alt_text ?: $asset->display_name }}" loading="lazy">
                                @else
                                    <span><i class="fa-regular fa-image"></i></span>
                                @endif
                            </button>
                            <div class="media-card__foot">
                                <div class="media-card__names">
                                    <span class="media-card__name" title="{{ $asset->display_name }}">{{ $asset->display_name }}</span>
                                    <span class="media-card__sub">
                                        {{ $asset->size_for_humans }}@if ($asset->width && $asset->height) · {{ $asset->width }}×{{ $asset->height }}@endif
                                    </span>
                                </div>
                                <div class="media-card__menu">
                                    <button type="button" class="media-card__menu-btn" @click.stop="menu = !menu"
                                        :aria-expanded="menu ? 'true' : 'false'" aria-label="{{ __('Actions for') }} {{ $asset->display_name }}">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <div class="media-card__menu-panel" x-show="menu" x-cloak style="display:none;" @click.stop
                                        x-transition:enter="media-menu-enter"
                                        x-transition:enter-start="media-menu-from"
                                        x-transition:enter-end="media-menu-to">
                                        <button type="button" @click="menu = false; openDetail(@js($assetPayload))">
                                            <i class="fa-solid fa-sliders"></i>{{ __('Details & edit') }}
                                        </button>
                                        <button type="button" @click="menu = false; copyText(@js($asset->url), 'URL copied')">
                                            <i class="fa-regular fa-copy"></i>{{ __('Copy URL') }}
                                        </button>
                                        <button type="button" @click="menu = false; copyText(@js($asset->reference), 'Path copied')">
                                            <i class="fa-solid fa-link"></i>{{ __('Copy path') }}
                                        </button>
                                        @if ($usage['count'] > 0)
                                            <span class="is-locked" title="{{ $usage['label'] }}">
                                                <i class="fa-solid fa-lock"></i>{{ __('In use') }} · {{ $usage['count'] }}
                                            </span>
                                        @else
                                            <button type="button" class="is-danger"
                                                @click="menu = false; openDelete({
                                                    name: @js($asset->display_name),
                                                    url: @js($asset->thumbnail_url ?: $asset->url),
                                                    path: @js($asset->path),
                                                    size: @js($asset->size_for_humans),
                                                    dimensions: @js($asset->width && $asset->height ? $asset->width.'x'.$asset->height : 'Unknown'),
                                                    action: @js(route('admin.media.destroy', $asset)),
                                                })">
                                                <i class="fa-solid fa-trash"></i>{{ __('Delete') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <x-admin.empty-state class="my-4" icon="fa-solid fa-photo-film" title="{{ __('No media files found') }}"
                    message="{{ __('Upload reusable product, banner, brand, collection, or content images.') }}" />
            @endif

            <x-table-footer :paginator="$assets" label="{{ __('media files') }}" />
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
                            <h3>{{ __('Delete media file?') }}</h3>
                            <p>{{ __('This unused image will be permanently removed from the media library.') }}</p>
                        </div>
                        <button type="button" class="form-modal__close" @click="closeDelete()" aria-label="{{ __('Close') }}">
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
                        <button type="button" class="modal-cancel" @click="closeDelete()">{{ __('Cancel') }}</button>
                        <button type="submit" class="form-submit-button media-delete-confirm">
                            <i class="fa-solid fa-trash-can"></i><span>{{ __('Delete image') }}</span>
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
                            <h3>{{ __('Upload Media') }}</h3>
                        <p>{{ __('Every upload can be used anywhere — products, banners, brands or content.') }}</p>
                    </div>
                    <button type="button" class="form-modal__close" @click="uploadOpen = false" aria-label="{{ __('Close') }}">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="form-modal__body">
                    @csrf
                    <div class="form-grid">
                        {{-- size="sm" keeps the control the same height as the sibling .form-input fields --}}
                        <x-select name="folder" size="sm" :label="__('Folder (optional)')" :options="$folders"
                            :value="old('folder')" placeholder="{{ __('Media Library') }}"
                            :help="__('Only for organizing. Any image can be picked by any feature.')" />
                        <div class="form-field">
                            <label for="title">{{ __('Title') }}</label>
                            <input id="title" name="title" value="{{ old('title') }}" class="form-input"
                                placeholder="{{ __('Optional display name') }}">
                            @error('title')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-field">
                            <label for="alt_text">{{ __('Alt text') }}</label>
                            <input id="alt_text" name="alt_text" value="{{ old('alt_text') }}" class="form-input"
                                placeholder="{{ __('Short image description') }}">
                            @error('alt_text')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-field">
                            <label for="tags">{{ __('Tags') }}</label>
                            <input id="tags" name="tags" value="{{ old('tags') }}" class="form-input"
                                placeholder="{{ __('summer, hero, lookbook') }}">
                            @error('tags')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-field sm:col-span-2">
                            <label for="media_files">{{ __('Images') }} <span class="text-red-500">*</span></label>
                            <label for="media_files" class="media-dropzone" :class="{ 'is-dragging': uploadDragging, 'has-files': uploadFileNames.length }"
                                @dragover.prevent="uploadDragging = true"
                                @dragleave.prevent="uploadDragging = false"
                                @drop.prevent="uploadDragging = false; dropUpload($event)">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span x-text="uploadFileNames.length ? `${uploadFileNames.length} file${uploadFileNames.length === 1 ? '' : 's'} selected` : @js(__('Choose or drop images'))"></span>
                                <small x-text="uploadFileNames.length ? uploadFileNames.slice(0, 3).join(', ') + (uploadFileNames.length > 3 ? ' +' + (uploadFileNames.length - 3) + ' more' : '') : @js(__('JPG, PNG, WebP, SVG or GIF. Up to :files files, :size MB each. Duplicates are skipped automatically.', ['files' => config('media.max_files'), 'size' => round(config('media.max_file_kb') / 1024)]))"></small>
                            </label>
                            <input id="media_files" x-ref="mediaFiles" type="file" name="files[]" accept="image/*" multiple class="visually-hidden" required
                                @change="setUploadFiles($event.target.files)">
                            @error('files')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                            @error('files.*')<p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="form-modal__foot">
                        <button type="button" class="modal-cancel" @click="uploadOpen = false">{{ __('Cancel') }}</button>
                        <button type="submit" class="form-submit-button">
                            <i class="fa-solid fa-upload"></i><span>{{ __('Upload files') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Detail drawer: preview on the left, editable metadata on the right. --}}
        <div class="modal-backdrop-premium media-detail-backdrop" x-show="detailOpen" x-cloak style="display:none;"
            x-transition:enter="media-field-backdrop-enter"
            x-transition:enter-start="media-field-backdrop-from"
            x-transition:enter-end="media-field-backdrop-to"
            x-transition:leave="media-field-backdrop-leave"
            x-transition:leave-start="media-field-backdrop-to"
            x-transition:leave-end="media-field-backdrop-from"
            @keydown.escape.window="detailOpen = false" @click.self="detailOpen = false">
            <div class="form-modal media-detail-modal"
                x-transition:enter="media-field-panel-enter"
                x-transition:enter-start="media-field-panel-from"
                x-transition:enter-end="media-field-panel-to"
                x-transition:leave="media-field-panel-leave"
                x-transition:leave-start="media-field-panel-to"
                x-transition:leave-end="media-field-panel-from">
                <div class="media-detail-modal__image">
                    <template x-if="detailAsset?.url">
                        <img :src="detailAsset.url" :alt="detailAsset.alt || detailAsset.name">
                    </template>
                </div>
                <div class="media-detail-modal__body">
                    <div class="form-modal__head">
                        <div class="form-modal__icon"><i class="fa-solid fa-photo-film"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <h3 x-text="detailAsset?.name || 'Media details'"></h3>
                            <p x-text="detailAsset?.path || ''"></p>
                        </div>
                        <button type="button" class="form-modal__close" @click="detailOpen = false" aria-label="{{ __('Close') }}">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form method="POST" :action="detailAsset?.updateAction || '#'" class="media-detail-form">
                        @csrf
                        @method('PATCH')
                        <div class="form-field">
                            <label for="detail_title">{{ __('Title') }}</label>
                            <input id="detail_title" name="title" class="form-input" :value="detailAsset?.title || ''"
                                placeholder="{{ __('Display name in the library') }}">
                        </div>
                        <div class="form-field">
                            <label for="detail_alt">{{ __('Alt text') }}</label>
                            <input id="detail_alt" name="alt_text" class="form-input" :value="detailAsset?.alt || ''"
                                placeholder="{{ __('Describe the image for search engines and screen readers') }}">
                            <p class="form-help">{{ __('Used as the image alt attribute wherever this asset is placed.') }}</p>
                        </div>
                        <div class="form-field">
                            <label for="detail_tags">{{ __('Tags') }}</label>
                            <input id="detail_tags" name="tags" class="form-input" :value="detailAsset?.tags || ''"
                                placeholder="{{ __('Comma separated') }}">
                        </div>
                        <div class="form-field">
                            <label for="detail_folder">{{ __('Folder') }}</label>
                            {{-- x-effect re-syncs the native value whenever another asset is opened. --}}
                            <select id="detail_folder" name="folder" class="form-input"
                                x-effect="$el.value = detailAsset?.folderKey || 'media'">
                                @foreach ($folders as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="media-detail-form__foot">
                            <button type="submit" class="form-submit-button">
                                <i class="fa-solid fa-floppy-disk"></i><span>{{ __('Save details') }}</span>
                            </button>
                            <button type="button" class="modal-cancel" @click="copyText(detailAsset?.url, 'URL copied')">
                                <i class="fa-regular fa-copy"></i><span>{{ __('Copy URL') }}</span>
                            </button>
                        </div>
                    </form>

                    <div class="media-detail-list">
                        <span><b>{{ __('Folder') }}</b><em x-text="detailAsset?.folder"></em></span>
                        <span><b>{{ __('Storage') }}</b><em x-text="detailAsset?.storage"></em></span>
                        <span><b>{{ __('Size') }}</b><em x-text="detailAsset?.size"></em></span>
                        <span><b>{{ __('Original') }}</b><em x-text="detailAsset?.originalSize"></em></span>
                        <span><b>{{ __('Dimensions') }}</b><em x-text="detailAsset?.dimensions"></em></span>
                        <span><b>{{ __('Uploaded') }}</b><em x-text="detailAsset?.uploaded || 'Unknown'"></em></span>
                    </div>
                    <div class="media-detail-optimization" :class="`is-${detailAsset?.optimizationStatus || 'pending'}`">
                        <strong x-text="detailAsset?.optimization || 'Pending optimization'"></strong>
                        <p x-text="detailAsset?.optimizationNotes || @js(__('The media library keeps optimization details for future cleanup and performance checks.'))"></p>
                    </div>
                    <div class="media-detail-usage" :class="{ 'is-used': (detailAsset?.usage?.count || 0) > 0 }">
                        <strong x-text="detailAsset?.usage?.count ? @js(__('Protected media')) : @js(__('Unused media'))"></strong>
                        <p x-text="detailAsset?.usage?.count ? @js(__('This file cannot be deleted until these references are removed.')) : @js(__('This file is not linked to catalog or content records.'))"></p>
                        <template x-if="detailAsset?.usage?.items?.length">
                            <div>
                                <template x-for="item in detailAsset.usage.items" :key="item.label">
                                    <span><b x-text="item.label"></b><em x-text="item.count"></em></span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
