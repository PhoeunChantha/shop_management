<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faq\StoreFaqRequest;
use App\Http\Requests\Faq\UpdateFaqRequest;
use App\Models\Faq;
use App\Services\BulkActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FaqController extends Controller
{
    use HandlesBulkActions;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Faq::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $faqs = Faq::query()
            ->search($search)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.faqs.index', ['faqs' => $faqs, 'perPage' => $perPage]);
    }

    public function create(): View
    {
        $this->authorize('create', Faq::class);

        return view('admin.faqs.create', ['categories' => $this->categories()]);
    }

    public function store(StoreFaqRequest $request): RedirectResponse
    {
        $this->authorize('create', Faq::class);

        try {
            Faq::create($request->validated());

            return to_route('admin.faqs.index')->with('success', 'FAQ created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating FAQ: '.$e->getMessage(), ['exception' => $e]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while creating the FAQ.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Faq::class);

        return view('admin.faqs.edit', ['faq' => Faq::findOrFail($id), 'categories' => $this->categories()]);
    }

    public function update(UpdateFaqRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Faq::class);

        try {
            Faq::findOrFail($id)->update($request->validated());

            return to_route('admin.faqs.index')->with('success', 'FAQ updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating FAQ: '.$e->getMessage(), ['exception' => $e, 'faq_id' => $id]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while updating the FAQ.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Faq::class);

        try {
            Faq::findOrFail($id)->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting FAQ: '.$e->getMessage(), ['exception' => $e, 'faq_id' => $id]);

            return back()->withErrors(['error' => 'An error occurred while deleting the FAQ.']);
        }

        return to_route('admin.faqs.index')->with('success', 'FAQ deleted successfully!');
    }

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', Faq::class);

        $result = $bulk->destroy(Faq::class, $this->validatedIds($request));

        return back()->with($this->bulkFlash($result, 'FAQ', 'in use'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', Faq::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $bulk->setStatus(Faq::class, $ids, $status);

        return back()->with('success', $count.' FAQ(s) '.($status ? 'enabled' : 'disabled').'.');
    }

    /**
     * Existing categories, for the datalist suggestions.
     *
     * @return array<int, string>
     */
    private function categories(): array
    {
        return Faq::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }
}
