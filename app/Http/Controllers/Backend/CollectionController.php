<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Backend\Concerns\ResolvesMediaSelection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Collection\StoreCollectionRequest;
use App\Http\Requests\Collection\UpdateCollectionRequest;
use App\Models\Collection;
use App\Models\Product;
use App\Services\BulkActionService;
use App\Services\ImageFieldService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CollectionController extends Controller
{
    use HandlesBulkActions;
    use ResolvesMediaSelection;

    public function __construct(
        private readonly ImageFieldService $images,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Collection::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $collections = Collection::query()
            ->withCount('products')
            ->search($search)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.collections.index', [
            'collections' => $collections,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Collection::class);

        return view('admin.collections.create', [
            'products' => $this->productOptions(),
            'selected' => old('products', []),
        ]);
    }

    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        $this->authorize('create', Collection::class);

        try {
            $validated = $request->safe()->except(['image', 'image_media', 'products']);
            $validated['slug'] = $this->uniqueSlug($validated['name']);

            $collection = Collection::create($validated);

            if ($request->hasFile('image')) {
                $this->images->attachUploaded($collection, $request->file('image'), 'collections');
            } elseif ($selected = $this->selectedMediaFilename($request, 'image', 'collections')) {
                $this->images->attachSelected($collection, $selected);
            }

            $collection->products()->sync($request->input('products', []));

            return to_route('admin.collections.index')->with('success', 'Collection created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating collection: '.$e->getMessage(), ['exception' => $e, 'request_data' => $request->except('image')]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while creating the collection.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Collection::class);

        $collection = Collection::with('products:id')->findOrFail($id);

        return view('admin.collections.edit', [
            'collection' => $collection,
            'products' => $this->productOptions(),
            'selected' => old('products', $collection->products->pluck('id')->all()),
        ]);
    }

    public function update(UpdateCollectionRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Collection::class);

        try {
            $collection = Collection::findOrFail($id);

            $validated = $request->safe()->except(['image', 'image_media', 'products']);
            $validated['slug'] = $this->uniqueSlug($validated['name'], $collection->id);

            $collection->update($validated);

            if ($request->hasFile('image')) {
                $this->images->replaceUploaded($collection, $request->file('image'), 'collections');
            } elseif ($selected = $this->selectedMediaFilename($request, 'image', 'collections')) {
                $this->images->attachSelected($collection, $selected);
            }

            $collection->products()->sync($request->input('products', []));

            return to_route('admin.collections.index')->with('success', 'Collection updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating collection: '.$e->getMessage(), ['exception' => $e, 'request_data' => $request->except('image'), 'collection_id' => $id]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while updating the collection.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Collection::class);

        try {
            $collection = Collection::findOrFail($id);
            $this->images->delete($collection->image, 'collections');
            $collection->delete(); // pivot rows cascade
        } catch (\Exception $e) {
            Log::error('Error deleting collection: '.$e->getMessage(), ['exception' => $e, 'collection_id' => $id]);

            return back()->withErrors(['error' => 'An error occurred while deleting the collection.']);
        }

        return to_route('admin.collections.index')->with('success', 'Collection deleted successfully!');
    }

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', Collection::class);

        $result = $bulk->destroy(Collection::class, $this->validatedIds($request), 'collections');

        return back()->with($this->bulkFlash($result, 'collection', 'in use'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', Collection::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $bulk->setStatus(Collection::class, $ids, $status);

        return back()->with('success', $count.' collection(s) '.($status ? 'enabled' : 'disabled').'.');
    }

    /**
     * Products for the picker: id, name and thumbnail URL.
     *
     * @return array<int, array<string, mixed>>
     */
    private function productOptions(): array
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'thumbnail'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'thumb' => $p->thumbnail ? Imageurl($p->thumbnail, 'products') : null,
            ])
            ->all();
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'collection';
        $slug = $base;
        $suffix = 2;

        while (
            Collection::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
