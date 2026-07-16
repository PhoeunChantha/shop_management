<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ImageManager;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Services\MediaOptimizationService;
use App\Services\MediaUsageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MediaAssetController extends Controller
{
    private const FOLDERS = [
        'media' => 'Media Library',
        'products' => 'Products',
        'variants' => 'Product Variants',
        'banners' => 'Banners',
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

        $search = trim($filters['search'] ?? '');
        $folder = $filters['folder'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 24);

        $assets = MediaAsset::query()
            ->with('user:id,name')
            ->search($search)
            ->when($folder, fn ($query) => $query->where('folder', $folder))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.media.index', [
            'assets' => $assets,
            'folders' => self::FOLDERS,
            'perPage' => $perPage,
            'totalSize' => MediaAsset::sum('size'),
            'totalAssets' => MediaAsset::count(),
            'optimizedAssets' => MediaAsset::whereIn('optimization_status', ['optimized', 'kept_original'])->count(),
            'totalSaved' => MediaAsset::query()
                ->whereColumn('original_size', '>', 'optimized_size')
                ->selectRaw('COALESCE(SUM(original_size - optimized_size), 0) as saved')
                ->value('saved') ?: 0,
            'usageMap' => $mediaUsage->summaryMap($assets->getCollection()),
        ]);
    }

    public function store(Request $request, MediaOptimizationService $optimizer): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'folder' => ['required', Rule::in(array_keys(self::FOLDERS))],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'files' => ['required', 'array', 'min:1', 'max:12'],
            'files.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg,gif', 'max:4096'],
        ]);

        $created = collect();

        try {
            foreach ($request->file('files', []) as $file) {
                $dimensions = @getimagesize($file->getRealPath()) ?: null;
                $mimeType = $file->getMimeType();
                $size = $file->getSize() ?: 0;
                $filename = ImageManager::upload($file, $validated['folder']);

                $asset = MediaAsset::create([
                    'user_id' => $request->user()?->id,
                    'folder' => $validated['folder'],
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'original_size' => $size,
                    'optimized_size' => $size,
                    'width' => $dimensions[0] ?? null,
                    'height' => $dimensions[1] ?? null,
                    'optimization_status' => 'pending',
                    'alt_text' => $validated['alt_text'] ?? null,
                ]);

                $asset->update($optimizer->optimize($asset));
                $created->push($asset);
            }
        } catch (\Throwable $e) {
            Log::error('Error uploading media asset: '.$e->getMessage(), ['exception' => $e]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'An error occurred while uploading media.'], 422);
            }

            return back()->withInput()->withErrors(['files' => 'An error occurred while uploading media.']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $created->map(fn (MediaAsset $asset) => $this->assetPayload($asset))->values(),
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

        $assets = MediaAsset::query()
            ->where('folder', $filters['folder'])
            ->search(trim($filters['search'] ?? ''))
            ->latest()
            ->limit(48)
            ->get()
            ->map(fn (MediaAsset $asset) => $this->assetPayload($asset));

        return response()->json(['data' => $assets]);
    }

    public function optimizePending(MediaOptimizationService $optimizer): RedirectResponse
    {
        $assets = MediaAsset::query()
            ->whereIn('optimization_status', ['pending', 'failed'])
            ->oldest()
            ->limit(100)
            ->get();

        $assets->each(function (MediaAsset $asset) use ($optimizer): void {
            $asset->update($optimizer->optimize($asset));
        });

        return back()->with('success', $assets->count().' media file(s) processed for optimization.');
    }

    public function destroy(
        MediaAsset $media,
        MediaUsageService $mediaUsage,
        MediaOptimizationService $optimizer
    ): RedirectResponse {
        $usages = $mediaUsage->usages($media);

        if ($usages !== []) {
            $labels = collect($usages)
                ->map(fn (array $usage): string => $usage['label'].' ('.$usage['count'].')')
                ->join(', ');

            return back()->withErrors([
                'error' => 'This media file is still used by '.$labels.'. Remove those references before deleting it.',
            ]);
        }

        try {
            $optimizer->deleteThumbnail($media);
            ImageManager::delete($media->filename, $media->folder);
            $media->delete();
        } catch (\Throwable $e) {
            Log::error('Error deleting media asset: '.$e->getMessage(), ['exception' => $e, 'media_id' => $media->id]);

            return back()->withErrors(['error' => 'An error occurred while deleting this media file.']);
        }

        return back()->with('success', 'Media file deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function assetPayload(MediaAsset $asset): array
    {
        return [
            'id' => $asset->id,
            'filename' => $asset->filename,
            'name' => $asset->original_name ?: $asset->filename,
            'url' => $asset->url,
            'thumbnail_url' => $asset->thumbnail_url,
            'size' => $asset->size_for_humans,
            'original_size' => $asset->original_size_for_humans,
            'optimized_size' => $asset->optimized_size_for_humans,
            'optimization_status' => $asset->optimization_status,
            'optimization_label' => $asset->optimization_label,
            'dimensions' => $asset->width && $asset->height ? $asset->width.'x'.$asset->height : null,
        ];
    }
}
