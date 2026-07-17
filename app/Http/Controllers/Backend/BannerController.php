<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Backend\Concerns\ResolvesMediaSelection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Banner\StoreBannerRequest;
use App\Http\Requests\Banner\UpdateBannerRequest;
use App\Models\Banner;
use App\Services\BulkActionService;
use App\Services\ImageFieldService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BannerController extends Controller
{
    use HandlesBulkActions;
    use ResolvesMediaSelection;

    public function __construct(
        private readonly ImageFieldService $images,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Banner::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $banners = Banner::query()
            ->search($search)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.banners.index', [
            'banners' => $banners,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Banner::class);

        return view('admin.banners.create');
    }

    public function store(StoreBannerRequest $request): RedirectResponse
    {
        $this->authorize('create', Banner::class);

        try {
            $validated = $request->safe()->except(['image', 'image_media']);
            $banner = Banner::create($validated);

            if ($request->hasFile('image')) {
                $this->images->attachUploaded($banner, $request->file('image'), 'banners');
            } elseif ($selected = $this->selectedMediaFilename($request, 'image', 'banners')) {
                $this->images->attachSelected($banner, $selected);
            }

            return to_route('admin.banners.index')->with('success', 'Banner created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating banner: '.$e->getMessage(), ['exception' => $e, 'request_data' => $request->except('image')]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while creating the banner.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Banner::class);

        return view('admin.banners.edit', ['banner' => Banner::findOrFail($id)]);
    }

    public function update(UpdateBannerRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Banner::class);

        try {
            $banner = Banner::findOrFail($id);
            $banner->update($request->safe()->except(['image', 'image_media']));

            if ($request->hasFile('image')) {
                $this->images->replaceUploaded($banner, $request->file('image'), 'banners');
            } elseif ($selected = $this->selectedMediaFilename($request, 'image', 'banners')) {
                $this->images->attachSelected($banner, $selected);
            }

            return to_route('admin.banners.index')->with('success', 'Banner updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating banner: '.$e->getMessage(), ['exception' => $e, 'request_data' => $request->except('image'), 'banner_id' => $id]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while updating the banner.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Banner::class);

        try {
            $banner = Banner::findOrFail($id);
            $this->images->delete($banner->image, 'banners');
            $banner->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting banner: '.$e->getMessage(), ['exception' => $e, 'banner_id' => $id]);

            return back()->withErrors(['error' => 'An error occurred while deleting the banner.']);
        }

        return to_route('admin.banners.index')->with('success', 'Banner deleted successfully!');
    }

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', Banner::class);

        $result = $bulk->destroy(Banner::class, $this->validatedIds($request), 'banners');

        return back()->with($this->bulkFlash($result, 'banner', 'in use'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', Banner::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $bulk->setStatus(Banner::class, $ids, $status);

        return back()->with('success', $count.' banner(s) '.($status ? 'enabled' : 'disabled').'.');
    }
}
