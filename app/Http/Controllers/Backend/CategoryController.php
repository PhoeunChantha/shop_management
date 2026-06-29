<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ImageManager;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller implements HasMiddleware
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

        $categories = Category::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.categories.index', [
            'categories' => $categories,
            'perPage' => $perPage,
        ]);
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.categories.create')
                ->withErrors($validator)
                ->withInput();
        }

        $category = new Category;
        $category->name = $request->name;
        $category->description = $request->description;
        $category->sort_order = $request->sort_order ?? 0;
        $category->status = $request->status;
        $category->icon = $request->icon;
        $category->slug = $this->uniqueSlug($request->name);

        if ($request->hasFile('image')) {
            $category->image = ImageManager::upload($request->file('image'), 'categories');
        }

        $category->save();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully!');
    }

    public function edit(string $id)
    {
        $category = Category::findOrFail($id);

        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.categories.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $category->name = $request->name;
        $category->description = $request->description;
        $category->sort_order = $request->sort_order ?? 0;
        $category->status = $request->status;
        $category->icon = $request->icon;
        $category->slug = $this->uniqueSlug($request->name, $category->id);

        $category->image = ImageManager::update($request->file('image'), $category->image, 'categories');

        $category->save();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully!');
    }

    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        ImageManager::delete($category->image, 'categories');

        $category->delete();

        return redirect()->route('admin.categories.index')
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
