<?php

namespace App\Http\Controllers\Backend;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\InventoryService;
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
