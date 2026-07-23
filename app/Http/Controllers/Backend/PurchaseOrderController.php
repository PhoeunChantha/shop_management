<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $purchaseOrders) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('view purchase orders'), 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_keys(PurchaseOrder::STATUSES))],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
        $perPage = (int) ($filters['per_page'] ?? 10);

        return view('admin.purchase-orders.index', [
            'purchaseOrders' => $this->purchaseOrders->paginate($filters, $perPage),
            'stats' => $this->purchaseOrders->stats(),
            'suppliers' => $this->purchaseOrders->supplierOptions(),
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('create purchase orders'), 403);

        return view('admin.purchase-orders.create', [
            'suppliers' => $this->purchaseOrders->supplierOptions(),
            'stockables' => $this->purchaseOrders->stockableOptions(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        try {
            $purchaseOrder = $this->purchaseOrders->create($request->validated());

            return to_route('admin.purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order created.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): View
    {
        abort_unless($request->user()->hasPermissionTo('view purchase orders'), 403);

        return view('admin.purchase-orders.show', [
            'purchaseOrder' => $this->purchaseOrders->findForShow($purchaseOrder),
        ]);
    }

    public function markOrdered(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('edit purchase orders'), 403);

        return $this->runAction(fn () => $this->purchaseOrders->markOrdered($purchaseOrder), 'Purchase order marked as ordered.');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('edit purchase orders'), 403);

        return $this->runAction(fn () => $this->purchaseOrders->receive($purchaseOrder), 'Purchase order received and stock updated.');
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('edit purchase orders'), 403);

        return $this->runAction(fn () => $this->purchaseOrders->cancel($purchaseOrder), 'Purchase order cancelled.');
    }

    private function runAction(callable $action, string $message): RedirectResponse
    {
        try {
            $action();
            return back()->with('success', $message);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
