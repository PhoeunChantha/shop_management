<?php

namespace App\Http\Controllers\Backend;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\PurchaseOrderService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly StockService $stock,
        private readonly PurchaseOrderService $purchaseOrders,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'stock' => ['nullable', 'in:in_stock,low_stock,out_of_stock'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        return view('admin.inventory.index', [
            'products' => $this->inventory->paginate($filters, $perPage),
            'perPage' => $perPage,
        ]);
    }

    public function show(string $id): View
    {
        $this->authorize('viewAny', Product::class);

        return view('admin.inventory.show', [
            'product' => $this->inventory->showProduct($id),
        ]);
    }

    public function reorder(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'in:low,out'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $dashboard = $this->inventory->reorderDashboard($filters, $perPage);

        return view('admin.inventory.reorder', [
            'alerts' => $dashboard['rows'],
            'stats' => $dashboard['stats'],
            'suppliers' => $this->purchaseOrders->supplierOptions(),
            'perPage' => $perPage,
        ]);
    }

    public function updateReorderRules(Request $request): RedirectResponse
    {
        $this->authorize('update', Product::class);

        $data = $request->validate([
            'rules' => ['required', 'array'],
            'rules.*.low_stock_alert' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $updated = $this->inventory->updateReorderRules($data['rules']);

        return back()->with('success', $updated.' reorder '.str('rule')->plural($updated).' updated.');
    }

    public function createPurchaseOrder(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('create purchase orders'), 403);

        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'status' => ['required', 'in:draft,ordered'],
            'expected_at' => ['nullable', 'date'],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['string', 'max:50'],
            'quantities' => ['nullable', 'array'],
            'quantities.*' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        try {
            $purchaseOrder = $this->purchaseOrders->create([
                'supplier_id' => $data['supplier_id'],
                'status' => $data['status'],
                'expected_at' => $data['expected_at'] ?? null,
                'notes' => 'Created from inventory reorder alerts.',
                'items' => $this->inventory->purchaseOrderItems($data['selected'], $data['quantities'] ?? []),
            ]);

            return to_route('admin.purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase order created from reorder alerts.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function adjust(Request $request, string $id): RedirectResponse
    {
        $this->authorize('update', Product::class);

        $product = $this->inventory->adjustmentProduct($id);

        $data = $request->validate([
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000'],
            'type' => ['required', 'in:'.implode(',', array_keys(StockMovementType::manualOptions()))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $item = $this->inventory->resolveStockable($product, ! empty($data['variant_id']) ? (int) $data['variant_id'] : null);
            $movement = $this->stock->adjust(
                $item,
                (int) $data['quantity'],
                StockMovementType::from($data['type']),
                $data['note'] ?? null,
            );

            return back()->with('success', 'Stock updated — now '.$movement->stock_after.' on hand.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error adjusting stock: '.$e->getMessage(), ['exception' => $e, 'product_id' => $id]);

            return back()->with('error', 'An error occurred while adjusting stock.');
        }
    }
}
