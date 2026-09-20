<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\BulkMediaRequest;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Http\Requests\Media\UpdateMediaRequest;
use App\Models\MediaAsset;
use App\Services\Admin\MediaAssetService;
use App\Services\Admin\MediaStorageService;
use App\Services\Admin\MediaUsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MediaAssetController extends Controller
{
    public function __construct(
        private readonly MediaAssetService $mediaAssets,
    ) {}

    private const FOLDERS = [
        'media' => 'Media Library',
        'products' => 'Products',
        'variants' => 'Product Variants',
        'banners' => 'Banners',
        'deals' => 'Deals',
        'brands' => 'Brands',
        'categories' => 'Categories',
        'collections' => 'Collections',
        'settings' => 'Settings',
    ];

    /**
     * Library folders, shared with the Form Requests that validate against them.
     *
     * @return array<string, string>
     */
    public static function folders(): array
    {
        return self::FOLDERS;
    }

    public function index(Request $request, MediaUsageService $mediaUsage, MediaStorageService $storage): View
    {
        abort_unless($request->user()->can('view media'), 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'folder' => ['nullable', Rule::in(array_keys(self::FOLDERS))],
            'kind' => ['nullable', Rule::in(array_keys(MediaAssetService::KINDS))],
            'usage' => ['nullable', Rule::in(array_keys(MediaAssetService::USAGE_STATES))],
            'sort' => ['nullable', Rule::in(array_keys(MediaAssetService::SORTS))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'in:12,24,48,96'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 24);
        $assets = $this->mediaAssets->paginate($filters, $perPage);

        // Usage is resolved once per page (batched across every reference
        // table) and cached back onto the rows so the "unused" filter and the
        // least-used sort stay accurate without re-scanning on every request.
        $usageMap = $mediaUsage->summaryMap($assets->getCollection());
        $mediaUsage->syncCounts($assets->getCollection());

        return view('admin.media.index', array_merge([
            'assets' => $assets,
            'folders' => self::FOLDERS,
            'sorts' => MediaAssetService::SORTS,
            'kinds' => MediaAssetService::KINDS,
            'usageStates' => MediaAssetService::USAGE_STATES,
            'perPage' => $perPage,
            'usageMap' => $usageMap,
            'storageLabel' => $storage->isRemote() ? strtoupper($storage->defaultDisk()) : 'Local disk',
        ], $this->mediaAssets->stats()));
    }

    public function store(StoreMediaRequest $request): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('create media'), 403);

        $validated = $request->validated();

        try {
            $result = $this->mediaAssets->store(
                $request->file('files', []),
                $validated['folder'],
                [
                    'title' => $validated['title'] ?? null,
                    'alt_text' => $validated['alt_text'] ?? null,
                    'tags' => $validated['tags'] ?? null,
                ],
                $request->user()?->id,
            );
        } catch (\Throwable $e) {
            Log::error('Error uploading media asset: '.$e->getMessage(), ['exception' => $e]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'An error occurred while uploading media.'], 422);
            }

            return back()->withInput()->withErrors(['files' => 'An error occurred while uploading media.']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $result['created']->map(fn (MediaAsset $asset) => $this->mediaAssets->payload($asset))->values(),
                'duplicates' => $result['duplicates']->count(),
            ]);
        }

        return back()->with('success', $this->uploadMessage($result));
    }

    public function update(UpdateMediaRequest $request, MediaAsset $media): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('edit media'), 403);

        $asset = $this->mediaAssets->update($media, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['data' => $this->mediaAssets->payload($asset)]);
        }

        return back()->with('success', __('Media details updated.'));
    }

    public function bulk(BulkMediaRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        abort_unless(
            $request->user()->can($validated['action'] === 'delete' ? 'delete media' : 'edit media'),
            403,
        );

        try {
            $result = $this->mediaAssets->bulk($validated['action'], $validated['ids'], $validated['folder'] ?? null);
        } catch (\Throwable $e) {
            Log::error('Error running media bulk action: '.$e->getMessage(), ['exception' => $e]);

            return back()->withErrors(['error' => __('An error occurred while applying that action.')]);
        }

        return back()->with('success', $this->bulkMessage($result));
    }

    public function picker(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('view media'), 403);

        // The folder is optional and only floats that folder's assets to the
        // top — the picker always offers the whole library, because one image
        // is meant to be usable by any feature.
        $filters = $request->validate([
            'folder' => ['nullable', Rule::in(array_keys(self::FOLDERS))],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json(['data' => $this->mediaAssets->picker($filters['folder'] ?? null, $filters['search'] ?? null)]);
    }

    public function optimizePending(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('edit media'), 403);

        $count = $this->mediaAssets->optimizePending();

        return back()->with('success', $count.' media file(s) processed for optimization.');
    }

    public function destroy(
        Request $request,
        MediaAsset $media,
    ): RedirectResponse {
        abort_unless($request->user()->can('delete media'), 403);

        try {
            $this->mediaAssets->delete($media);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Error deleting media asset: '.$e->getMessage(), ['exception' => $e, 'media_id' => $media->id]);

            return back()->withErrors(['error' => 'An error occurred while deleting this media file.']);
        }

        return back()->with('success', __('Media file deleted.'));
    }

    /**
     * @param  array{created: Collection<int, MediaAsset>, duplicates: Collection<int, MediaAsset>}  $result
     */
    private function uploadMessage(array $result): string
    {
        $message = $result['created']->count().' media file(s) uploaded.';

        if ($result['duplicates']->isNotEmpty()) {
            $message .= ' '.$result['duplicates']->count().' duplicate(s) skipped — those files are already in this folder.';
        }

        return $message;
    }

    /**
     * @param  array{action: string, affected: int, blocked: int}  $result
     */
    private function bulkMessage(array $result): string
    {
        $message = match ($result['action']) {
            'delete' => $result['affected'].' media file(s) deleted.',
            'move' => $result['affected'].' media file(s) moved.',
            'optimize' => $result['affected'].' media file(s) processed for optimization.',
            default => 'No changes applied.',
        };

        if ($result['blocked'] > 0) {
            $message .= ' '.$result['blocked'].' skipped — still referenced by catalog or content records.';
        }

        return $message;
    }
}
