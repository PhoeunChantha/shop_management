<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Services\MediaAssetService;
use App\Services\MediaUsageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function index(Request $request, MediaUsageService $mediaUsage): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'folder' => ['nullable', Rule::in(array_keys(self::FOLDERS))],
            'per_page' => ['nullable', 'integer', 'in:12,24,48,96'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 24);
        $assets = $this->mediaAssets->paginate($filters, $perPage);

        return view('admin.media.index', array_merge([
            'assets' => $assets,
            'folders' => self::FOLDERS,
            'perPage' => $perPage,
            'usageMap' => $mediaUsage->summaryMap($assets->getCollection()),
        ], $this->mediaAssets->stats()));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'folder' => ['required', Rule::in(array_keys(self::FOLDERS))],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'files' => ['required', 'array', 'min:1', 'max:12'],
            'files.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg,gif', 'max:4096'],
        ]);

        try {
            $created = $this->mediaAssets->store(
                $request->file('files', []),
                $validated['folder'],
                $validated['alt_text'] ?? null,
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
                'data' => $created->map(fn (MediaAsset $asset) => $this->mediaAssets->payload($asset))->values(),
            ]);
        }

        return back()->with('success', $created->count().' media file(s) uploaded.');
    }

    public function picker(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'folder' => ['required', Rule::in(array_keys(self::FOLDERS))],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json(['data' => $this->mediaAssets->picker($filters['folder'], $filters['search'] ?? null)]);
    }

    public function optimizePending(): RedirectResponse
    {
        $count = $this->mediaAssets->optimizePending();

        return back()->with('success', $count.' media file(s) processed for optimization.');
    }

    public function destroy(
        MediaAsset $media,
    ): RedirectResponse {
        try {
            $this->mediaAssets->delete($media);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Error deleting media asset: '.$e->getMessage(), ['exception' => $e, 'media_id' => $media->id]);

            return back()->withErrors(['error' => 'An error occurred while deleting this media file.']);
        }

        return back()->with('success', 'Media file deleted.');
    }
}
