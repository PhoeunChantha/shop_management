<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Models\Coupon;
use App\Services\BulkActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CouponController extends Controller
{
    use HandlesBulkActions;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Coupon::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $coupons = Coupon::query()
            ->search($search)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.coupons.index', [
            'coupons' => $coupons,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Coupon::class);

        return view('admin.coupons.create');
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $this->authorize('create', Coupon::class);

        try {
            Coupon::create($request->validated());

            return to_route('admin.coupons.index')
                ->with('success', 'Coupon created successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating coupon: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the coupon.']);
        }
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Coupon::class);

        $coupon = Coupon::findOrFail($id);

        return view('admin.coupons.edit', [
            'coupon' => $coupon,
        ]);
    }

    public function update(UpdateCouponRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Coupon::class);

        try {
            $coupon = Coupon::findOrFail($id);

            $coupon->update($request->validated());

            return to_route('admin.coupons.index')
                ->with('success', 'Coupon updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating coupon: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
                'coupon_id' => $id,
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the coupon.']);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Coupon::class);

        try {
            $coupon = Coupon::findOrFail($id);
            $coupon->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting coupon: '.$e->getMessage(), [
                'exception' => $e,
                'coupon_id' => $id,
            ]);

            return back()
                ->withErrors(['error' => 'An error occurred while deleting the coupon.']);
        }

        return to_route('admin.coupons.index')
            ->with('success', 'Coupon deleted successfully!');
    }

    public function bulkDestroy(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('delete', Coupon::class);

        $ids = $this->validatedIds($request);
        $result = $bulk->destroy(Coupon::class, $ids);

        return back()->with($this->bulkFlash($result, 'coupon', 'in use'));
    }

    public function bulkStatus(Request $request, BulkActionService $bulk): RedirectResponse
    {
        $this->authorize('update', Coupon::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $bulk->setStatus(Coupon::class, $ids, $status);

        return back()->with('success', $count.' coupon(s) '.($status ? 'enabled' : 'disabled').'.');
    }
}
