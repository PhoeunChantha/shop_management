<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ImageManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $categories = Category::query()
            ->search($search)
            ->orderBy('sort_order', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.categories.index', [
            'categories' => $categories,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        try {
            $validated = $request->safe()->except('image');
            $validated['slug'] = $this->uniqueSlug($validated['name']);

            $category = Category::create($validated);

            if ($request->hasFile('image')) {
                $category->image = ImageManager::upload($request->file('image'), 'categories');
                $category->save();
            }

            return to_route('admin.categories.index')
                ->with('success', 'Category created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating category: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except('image'),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the category.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Category::class);

        $category = Category::findOrFail($id);

        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Category::class);

        try {
            $category = Category::findOrFail($id);
            $validated = $request->safe()->except('image');
            $validated['slug'] = $this->uniqueSlug($validated['name'], $category->id);

            $category->update($validated);

            if ($request->hasFile('image')) {
                $category->image = ImageManager::update($request->file('image'), $category->image, 'categories');
                $category->save();
            }

            return to_route('admin.categories.index')
                ->with('success', 'Category updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating category: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except('image'),
                'category_id' => $id,
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the category.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Category::class);

        try {
            $category = Category::findOrFail($id);

            ImageManager::delete($category->image, 'categories');

            $category->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting category: '.$e->getMessage(), [
                'exception' => $e,
                'category_id' => $id,
            ]);

            return back()
                ->withErrors(['error' => 'An error occurred while deleting the category.']);
        }

        return to_route('admin.categories.index')
            ->with('success', 'Category deleted successfully!');
    }

    /**
     * Generate a URL-safe slug from the name, guaranteed unique on the categories table.
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 2;

        while (
            Category::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
