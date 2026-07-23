<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Page\StorePageRequest;
use App\Http\Requests\Page\UpdatePageRequest;
use App\Models\Page;
use App\Services\BulkActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    use HandlesBulkActions;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Page::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $pages = Page::query()
            ->search($search)
            ->orderBy('title')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.pages.index', ['pages' => $pages, 'perPage' => $perPage]);
    }

    public function create(): View
    {
        $this->authorize('create', Page::class);

        return view('admin.pages.create');
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $this->authorize('create', Page::class);

        try {
            $validated = $request->validated();
            $validated['slug'] = $this->uniqueSlug($validated['title']);

            Page::create($validated);

            return to_route('admin.pages.index')->with('success', 'Page created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating page: '.$e->getMessage(), ['exception' => $e]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while creating the page.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Page::class);

        return view('admin.pages.edit', ['page' => Page::findOrFail($id)]);
    }

    public function update(UpdatePageRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Page::class);

        try {
            $page = Page::findOrFail($id);
            $validated = $request->validated();
            $validated['slug'] = $this->uniqueSlug($validated['title'], $page->id);

            $page->update($validated);

            return to_route('admin.pages.index')->with('success', 'Page updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating page: '.$e->getMessage(), ['exception' => $e, 'page_id' => $id]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while updating the page.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Page::class);

        try {
            Page::findOrFail($id)->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting page: '.$e->getMessage(), ['exception' => $e, 'page_id' => $id]);

            return back()->withErrors(['error' => 'An error occurred while deleting the page.']);
        }

        return to_route('admin.pages.index')->with('success', 'Page deleted successfully!');
    }

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', Page::class);

        $result = $bulk->destroy(Page::class, $this->validatedIds($request));

        return back()->with($this->bulkFlash($result, 'page', 'in use'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', Page::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $bulk->setStatus(Page::class, $ids, $status);

        return back()->with('success', $count.' page(s) '.($status ? 'published' : 'unpublished').'.');
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'page';
        $slug = $base;
        $suffix = 2;

        while (
            Page::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
