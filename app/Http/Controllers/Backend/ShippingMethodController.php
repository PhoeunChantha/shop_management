<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\ShippingMethod\StoreShippingMethodRequest;
use App\Http\Requests\ShippingMethod\UpdateShippingMethodRequest;
use App\Models\ShippingMethod;
use App\Services\BulkActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ShippingMethodController extends Controller
{
    use HandlesBulkActions;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ShippingMethod::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $methods = ShippingMethod::query()
            ->search($search)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.shipping.index', ['methods' => $methods, 'perPage' => $perPage]);
    }

    public function create(): View
    {
        $this->authorize('create', ShippingMethod::class);

        return view('admin.shipping.create');
    }

    public function store(StoreShippingMethodRequest $request): RedirectResponse
    {
        $this->authorize('create', ShippingMethod::class);

        try {
            ShippingMethod::create($request->validated());

            return to_route('admin.shipping.index')->with('success', 'Shipping method created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating shipping method: '.$e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while creating the shipping method.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', ShippingMethod::class);

        return view('admin.shipping.edit', ['method' => ShippingMethod::findOrFail($id)]);
    }

    public function update(UpdateShippingMethodRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', ShippingMethod::class);

        try {
            ShippingMethod::findOrFail($id)->update($request->validated());

            return to_route('admin.shipping.index')->with('success', 'Shipping method updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating shipping method: '.$e->getMessage(), ['exception' => $e, 'request_data' => $request->all(), 'id' => $id]);

            return back()->withInput()->withErrors(['error' => 'An error occurred while updating the shipping method.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', ShippingMethod::class);

        try {
            ShippingMethod::findOrFail($id)->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting shipping method: '.$e->getMessage(), ['exception' => $e, 'id' => $id]);

            return back()->withErrors(['error' => 'An error occurred while deleting the shipping method.']);
        }

        return to_route('admin.shipping.index')->with('success', 'Shipping method deleted successfully!');
    }

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', ShippingMethod::class);

        $result = $bulk->destroy(ShippingMethod::class, $this->validatedIds($request));

        return back()->with($this->bulkFlash($result, 'shipping method', 'in use'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', ShippingMethod::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $bulk->setStatus(ShippingMethod::class, $ids, $status);

        return back()->with('success', $count.' shipping method(s) '.($status ? 'enabled' : 'disabled').'.');
    }
}
