<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Backend\Concerns\ResolvesMediaSelection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Admin\BulkActionService;
use App\Services\Admin\ImageFieldService;
use App\Services\Admin\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use HandlesBulkActions;
    use ResolvesMediaSelection;

    public function __construct(
        private readonly ImageFieldService $images,
        private readonly SettingService $settings,
    ) {}

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
            ->with('parent')
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

        return view('admin.categories.create', [
            'parentOptions' => Category::treeOptions(),
        ] + $this->formLocales());
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        try {
            $validated = $request->safe()->except(['image', 'image_media', 'seo_image', 'seo_image_media']);
            $validated['slug'] = $this->uniqueSlug($this->primaryName($validated));

            $category = Category::create($validated);

            $this->syncImages($request, $category);

            return to_route('admin.categories.index')
                ->with('success', __('Category created successfully!'));
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
            'parentOptions' => Category::treeOptions($category),
        ] + $this->formLocales());
    }

    public function update(UpdateCategoryRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Category::class);

        try {
            $category = Category::findOrFail($id);
            $validated = $request->safe()->except(['image', 'image_media', 'seo_image', 'seo_image_media']);
            $validated['slug'] = $this->uniqueSlug($this->primaryName($validated), $category->id);

            // Replace the translation sets wholesale so cleared languages are removed.
            foreach (['name', 'description', 'seo_title', 'seo_description', 'seo_keywords'] as $field) {
                $category->setTranslations($field, $validated[$field] ?? []);
                unset($validated[$field]);
            }

            $category->fill($validated)->save();

            $this->syncImages($request, $category);

            return to_route('admin.categories.index')
                ->with('success', __('Category updated successfully!'));
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

            if ($category->children()->exists()) {
                return back()->with('error', __('Cannot delete “:name” because it has sub-categories. Move or delete them first.', ['name' => $category->name]));
            }

            if ($category->isInUse()) {
                return back()->with('error', "Cannot delete “{$category->name}” because it is assigned to one or more products.");
            }

            $this->images->delete($category->image, 'categories');

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
            ->with('success', __('Category deleted successfully!'));
    }

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', Category::class);

        $ids = $this->validatedIds($request);
        $result = $bulk->destroy(Category::class, $ids, 'categories');

        return back()->with($this->bulkFlash($result, 'category', 'assigned to products or have sub-categories'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', Category::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $bulk->setStatus(Category::class, $ids, $status);

        return back()->with('success', $count.' category(s) '.($status ? 'enabled' : 'disabled').'.');
    }

    /**
     * Languages the form renders tabs for, plus the primary (required) one.
     *
     * @return array{locales: array<string, string>, primaryLang: string}
     */
    private function formLocales(): array
    {
        return [
            'locales' => $this->settings->activeLanguages(),
            'primaryLang' => $this->settings->primaryLanguage(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function primaryName(array $validated): string
    {
        $names = (array) ($validated['name'] ?? []);

        return (string) ($names[$this->settings->primaryLanguage()] ?? reset($names) ?: '');
    }

    /**
     * Persist the category image and the SEO/social image — each accepts either a
     * fresh upload or a pick from the media library.
     */
    private function syncImages(Request $request, Category $category): void
    {
        foreach (['image', 'seo_image'] as $field) {
            if ($request->hasFile($field)) {
                $category->{$field}
                    ? $this->images->replaceUploaded($category, $request->file($field), 'categories', $field)
                    : $this->images->attachUploaded($category, $request->file($field), 'categories', $field);
            } elseif ($selected = $this->selectedMediaFilename($request, $field, 'categories')) {
                $this->images->attachSelected($category, $selected, $field);
            }
        }
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
