<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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
            'search'   => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search  = trim($filters['search'] ?? '');

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
            'perPage'    => $perPage,
        ]);
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|min:2|max:255',
            'slug'        => 'nullable|string|unique:categories,slug',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'icon'        => 'nullable|string|max:255', 
            'sort_order'  => 'nullable|integer',
            'status'      => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.categories.create')
                ->withErrors($validator)
                ->withInput();
        }

        $category = new Category();
        $category->name        = $request->name;
        $category->description = $request->description;
        $category->sort_order  = $request->sort_order ?? 0; 
        $category->status      = $request->status;
        $category->icon        = $request->icon; 
        $category->slug = empty($request->slug) ? Str::slug($request->name) : Str::slug($request->slug);

        if ($request->hasFile('image')) {
            $category->image = $this->uploadFile($request->file('image'), 'uploads/categories/images');
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
            'name'        => 'required|min:2|max:255',
            'slug'        => 'nullable|string|unique:categories,slug,' . $id,
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'icon'        => 'nullable|string|max:255', 
            'sort_order'  => 'nullable|integer',
            'status'      => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.categories.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $category->name        = $request->name;
        $category->description = $request->description;
        $category->sort_order  = $request->sort_order ?? 0; 
        $category->status      = $request->status;
        $category->icon        = $request->icon; 
        $category->slug = empty($request->slug) ? Str::slug($request->name) : Str::slug($request->slug);

        if ($request->hasFile('image')) {
            $this->deleteFile($category->image);
            $category->image = $this->uploadFile($request->file('image'), 'uploads/categories/images');
        }

        $category->save();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully!');
    }

    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        $this->deleteFile($category->image);

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully!');
    }

    private function uploadFile($file, string $directory): string
    {
        $filename = $file->hashName();
        File::ensureDirectoryExists(public_path($directory));
        $file->move(public_path($directory), $filename);
        
        return $directory . '/' . $filename;
    }

    private function deleteFile(?string $filePath): void
    {
        if ($filePath && File::exists(public_path($filePath))) {
            File::delete(public_path($filePath));
        }
    }
}