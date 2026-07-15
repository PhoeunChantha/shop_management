<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Marketing</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Media Library') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page media-library-page" x-data="{ uploadOpen: false }">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Asset manager</p>
                <h3>Media Library</h3>
            </div>
            <button type="button" class="premium-button premium-button--dark" @click="uploadOpen = true">
                <i class="fa-solid fa-cloud-arrow-up"></i><span>Upload Media</span>
            </button>
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
        </div>

        <x-filter-card :action="route('admin.media.index')" class="mt-3">
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
                    <span class="media-toolbar-note">Use the asset URL for banners, products, brands, and content images.</span>
                </x-slot:right>
            </x-table-toolbar>

            @if ($assets->count())
                <div class="media-grid">
                    @foreach ($assets as $asset)
                        <article class="media-card">
                            <div class="media-card__preview">
                                @if ($asset->url)
                                    <img src="{{ $asset->url }}" alt="{{ $asset->alt_text ?: $asset->original_name ?: $asset->filename }}">
                                @else
                                    <span><i class="fa-regular fa-image"></i></span>
                                @endif
                            </div>
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
                                </div>
                                <div class="media-card__path" title="{{ $asset->path }}">{{ $asset->path }}</div>
                                <div class="media-card__actions">
                                    <button type="button" class="ghost-button media-copy-btn" data-copy-text="{{ $asset->url }}">
                                        <i class="fa-regular fa-copy"></i><span>Copy URL</span>
                                    </button>
                                    <form method="POST" action="{{ route('admin.media.destroy', $asset) }}" class="mb-0"
                                        onsubmit="return confirm('Delete this media file? Existing records that use this filename may show a missing image.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ghost-button media-delete-btn">
                                            <i class="fa-solid fa-trash"></i><span>Delete</span>
                                        </button>
                                    </form>
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
                            <label for="media_files" class="media-dropzone">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>Choose images</span>
                                <small>JPG, PNG, WebP, SVG or GIF. Up to 12 files, 4MB each.</small>
                            </label>
                            <input id="media_files" type="file" name="files[]" accept="image/*" multiple class="visually-hidden" required>
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
    </div>

    @push('js')
        <script>
            document.querySelectorAll('[data-copy-text]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const text = button.dataset.copyText || '';
                    if (!text) return;

                    try {
                        await navigator.clipboard.writeText(text);
                        const label = button.querySelector('span');
                        if (!label) return;
                        const original = label.textContent;
                        label.textContent = 'Copied';
                        setTimeout(() => label.textContent = original, 1200);
                    } catch (error) {
                        window.prompt('Copy media URL', text);
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
