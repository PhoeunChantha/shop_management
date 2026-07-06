<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        return view('admin.orders.index', [
            'orders' => $this->orders->paginate($filters, $perPage),
            'perPage' => $perPage,
        ]);
    }

    public function show(string $id): View
    {
        $this->authorize('view', Order::class);

        $order = Order::with(['details', 'user', 'coupon'])->findOrFail($id);

        return view('admin.orders.show', ['order' => $order]);
    }

    public function update(UpdateOrderRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Order::class);

        try {
            $order = Order::findOrFail($id);
            $this->orders->updateFulfilment($order, $request->validated());

            return to_route('admin.orders.show', $order->id)
                ->with('success', 'Order updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating order: '.$e->getMessage(), [
                'exception' => $e,
                'order_id' => $id,
            ]);

            return back()->withInput()
                ->withErrors(['error' => 'An error occurred while updating the order.']);
        }
    }
}
