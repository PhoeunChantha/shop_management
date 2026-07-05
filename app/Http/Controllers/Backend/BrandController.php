<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ImageManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Brand::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $brands = Brand::query()
            ->withCount('products')
            ->search($search)
            ->orderBy('name', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.brands.index', [
            'brands' => $brands,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Brand::class);

        return view('admin.brands.create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $this->authorize('create', Brand::class);

        try {
            $validated = $request->safe()->except('image');
            $validated['slug'] = $this->uniqueSlug($validated['name']);

            $brand = Brand::create($validated);

            if ($request->hasFile('image')) {
                $brand->image = ImageManager::upload($request->file('image'), 'brands');
                $brand->save();
            }

            return to_route('admin.brands.index')
                ->with('success', 'Brand created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating brand: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except('image'),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the brand.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Brand::class);

        $brand = Brand::findOrFail($id);

        return view('admin.brands.edit', [
            'brand' => $brand,
        ]);
    }

    public function update(UpdateBrandRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Brand::class);

        try {
            $brand = Brand::findOrFail($id);

            $validated = $request->safe()->except('image');
            $validated['slug'] = $this->uniqueSlug($validated['name'], $brand->id);

            $brand->update($validated);

            if ($request->hasFile('image')) {
                $brand->image = ImageManager::update($request->file('image'), $brand->image, 'brands');
                $brand->save();
            }

            return to_route('admin.brands.index')
                ->with('success', 'Brand updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating brand: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except('image'),
                'brand_id' => $id,
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the brand.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Brand::class);

        try {
            $brand = Brand::findOrFail($id);

            ImageManager::delete($brand->image, 'brands');

            $brand->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting brand: '.$e->getMessage(), [
                'exception' => $e,
                'brand_id' => $id,
            ]);

            return back()
                ->withErrors(['error' => 'An error occurred while deleting the brand.']);
        }

        return to_route('admin.brands.index')
            ->with('success', 'Brand deleted successfully!');
    }

    /**
     * Generate a URL-safe slug from the name, guaranteed unique on the brands table.
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'brand';
        $slug = $base;
        $suffix = 2;

        while (
            Brand::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
