<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Size\StoreSizeRequest;
use App\Http\Requests\Size\UpdateSizeRequest;
use App\Models\Size;
use App\Services\BulkActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SizeController extends Controller
{
    use HandlesBulkActions;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Size::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $sizes = Size::query()
            ->search($search)
            ->orderBy('sort_order', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.sizes.index', [
            'sizes' => $sizes,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Size::class);

        return view('admin.sizes.create');
    }

    public function store(StoreSizeRequest $request): RedirectResponse
    {
        $this->authorize('create', Size::class);

        try {
            $validated = $request->validated();
            $validated['sort_order'] ??= 0;

            Size::create($validated);

            return to_route('admin.sizes.index')
                ->with('success', 'Size created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating size: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the size.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Size::class);

        $size = Size::findOrFail($id);

        return view('admin.sizes.edit', [
            'size' => $size,
        ]);
    }

    public function update(UpdateSizeRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Size::class);

        try {
            $size = Size::findOrFail($id);

            $validated = $request->validated();
            $validated['sort_order'] ??= 0;

            $size->update($validated);

            return to_route('admin.sizes.index')
                ->with('success', 'Size updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating size: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
                'size_id' => $id,
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the size.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Size::class);

        try {
            $size = Size::findOrFail($id);

            if ($size->isInUse()) {
                return back()->with('error', "Cannot delete “{$size->name}” because it is used by one or more product variants.");
            }

            $size->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting size: '.$e->getMessage(), [
                'exception' => $e,
                'size_id' => $id,
            ]);

            return back()
                ->withErrors(['error' => 'An error occurred while deleting the size.']);
        }

        return to_route('admin.sizes.index')
            ->with('success', 'Size deleted successfully!');
    }

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', Size::class);

        $ids = $this->validatedIds($request);
        $result = $bulk->destroy(Size::class, $ids);

        return back()->with($this->bulkFlash($result, 'size', 'used by variants'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', Size::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $bulk->setStatus(Size::class, $ids, $status);

        return back()->with('success', $count.' size(s) '.($status ? 'enabled' : 'disabled').'.');
    }
}
