<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ImageManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(
                'role:admin|manager',
                only: ['index', 'edit', 'create', 'update', 'store', 'destroy']
            ),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $brands = Brand::query()
            ->withCount('products')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
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
        return view('admin.brands.create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $brand = new Brand;
        $brand->name = $request->validated('name');
        $brand->status = $request->validated('status');
        $brand->slug = $this->uniqueSlug($request->validated('name'));

        if ($request->hasFile('image')) {
            $brand->image = ImageManager::upload($request->file('image'), 'brands');
        }

        $brand->save();

        return redirect()->route('admin.brands.index')
            ->with('success', 'Brand created successfully!');
    }

    public function edit(string $id): View
    {
        $brand = Brand::findOrFail($id);

        return view('admin.brands.edit', [
            'brand' => $brand,
        ]);
    }

    public function update(UpdateBrandRequest $request, string $id): RedirectResponse
    {
        $brand = Brand::findOrFail($id);

        $brand->name = $request->validated('name');
        $brand->status = $request->validated('status');
        $brand->slug = $this->uniqueSlug($request->validated('name'), $brand->id);

        $brand->image = ImageManager::update($request->file('image'), $brand->image, 'brands');

        $brand->save();

        return redirect()->route('admin.brands.index')
            ->with('success', 'Brand updated successfully!');
    }

    public function destroy(string $id): RedirectResponse
    {
        $brand = Brand::findOrFail($id);

        ImageManager::delete($brand->image, 'brands');

        $brand->delete();

        return redirect()->route('admin.brands.index')
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
