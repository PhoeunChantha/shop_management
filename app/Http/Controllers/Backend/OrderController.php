<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly SettingService $settings,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'fulfillment_status' => ['nullable', 'string'],
            'payment_status' => ['nullable', 'string'],
            'customer' => ['nullable', 'integer'],
            'price' => ['nullable', 'string', 'in:'.implode(',', array_keys(OrderService::priceRanges()))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        return view('admin.orders.index', [
            'orders' => $this->orders->paginate($filters, $perPage),
            'perPage' => $perPage,
            'stats' => $this->orders->stats(),
            'customers' => User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))
                ->orderBy('name')->get(['id', 'name', 'email'])
                ->mapWithKeys(fn (User $u) => [$u->id => $u->name.' — '.$u->email]),
            'priceRanges' => OrderService::priceRanges(),
        ]);
    }

    public function show(string $id): View
    {
        $this->authorize('view', Order::class);

        $order = $this->orders->findForShow($id);

        return view('admin.orders.show', [
            'order' => $order,
            'customerStats' => $this->orders->customerStats($order),
        ]);
    }

    public function invoice(string $id): View
    {
        $this->authorize('view', Order::class);

        return view('admin.orders.print.invoice', [
            'order' => $this->orders->findForInvoice($id),
            'store' => $this->storeInfo(),
        ]);
    }

    public function packingSlip(string $id): View
    {
        $this->authorize('view', Order::class);

        return view('admin.orders.print.packing-slip', [
            'order' => $this->orders->findForPackingSlip($id),
            'store' => $this->storeInfo(),
        ]);
    }

    /**
     * Store identity for printable documents (from Settings).
     *
     * @return array<string, string|null>
     */
    private function storeInfo(): array
    {
        return [
            'name' => $this->settings->siteName(),
            'logo' => $this->settings->logoUrl(),
            'email' => Setting::get('contact_email'),
            'phone' => Setting::get('contact_phone'),
            'address' => Setting::get('contact_address'),
        ];
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
