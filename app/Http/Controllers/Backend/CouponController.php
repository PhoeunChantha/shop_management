<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
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
        return view('admin.coupons.create');
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
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
        $coupon = Coupon::findOrFail($id);

        return view('admin.coupons.edit', [
            'coupon' => $coupon,
        ]);
    }

    public function update(UpdateCouponRequest $request, string $id): RedirectResponse
    {
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
}
