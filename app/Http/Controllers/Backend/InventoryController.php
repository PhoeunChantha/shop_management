<?php

namespace App\Http\Controllers\Backend;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'stock' => ['nullable', 'in:in_stock,low_stock,out_of_stock'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');
        $stock = $filters['stock'] ?? null;

        $products = Product::query()
            ->with('brand')
            ->withCount('variants')
            ->withCount(['variants as low_variants_count' => fn ($v) => $v->where('low_stock_alert', '>', 0)->whereColumn('stock', '<=', 'low_stock_alert')])
            ->withSum('variants', 'stock')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
            ->when($stock === 'out_of_stock', fn ($q) => $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('product_type', 'single')->where('stock', '<=', 0))
                ->orWhere(fn ($q) => $q->where('product_type', 'variable')->whereDoesntHave('variants', fn ($v) => $v->where('stock', '>', 0)))))
            ->when($stock === 'low_stock', fn ($q) => $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('product_type', 'single')->where('low_stock_alert', '>', 0)->whereColumn('stock', '<=', 'low_stock_alert'))
                ->orWhere(fn ($q) => $q->where('product_type', 'variable')->whereHas('variants', fn ($v) => $v->where('low_stock_alert', '>', 0)->whereColumn('stock', '<=', 'low_stock_alert')))))
            ->when($stock === 'in_stock', fn ($q) => $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('product_type', 'single')->where('stock', '>', 0))
                ->orWhere(fn ($q) => $q->where('product_type', 'variable')->whereHas('variants', fn ($v) => $v->where('stock', '>', 0)))))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.inventory.index', [
            'products' => $products,
            'perPage' => $perPage,
        ]);
    }

    public function show(string $id): View
    {
        $this->authorize('viewAny', Product::class);

        $product = Product::with([
            'variants.values',
            'stockMovements.actor',
            'stockMovements.variant.values',
        ])->findOrFail($id);

        return view('admin.inventory.show', [
            'product' => $product,
        ]);
    }

    public function adjust(Request $request, string $id): RedirectResponse
    {
        $this->authorize('update', Product::class);

        $product = Product::with('variants')->findOrFail($id);

        $data = $request->validate([
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000'],
            'type' => ['required', 'in:'.implode(',', array_keys(StockMovementType::manualOptions()))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Resolve the stockable: a specific variant, or the single product itself.
        $item = $product;
        if (! empty($data['variant_id'])) {
            $item = $product->variants->firstWhere('id', (int) $data['variant_id']);
            if (! $item) {
                return back()->with('error', 'That variant does not belong to this product.');
            }
        } elseif ($product->product_type->value !== 'single') {
            return back()->with('error', 'Pick a variant to adjust for a variable product.');
        }

        try {
            $movement = $this->stock->adjust(
                $item,
                (int) $data['quantity'],
                StockMovementType::from($data['type']),
                $data['note'] ?? null,
            );

            return back()->with('success', 'Stock updated — now '.$movement->stock_after.' on hand.');
        } catch (\Exception $e) {
            Log::error('Error adjusting stock: '.$e->getMessage(), ['exception' => $e, 'product_id' => $id]);

            return back()->with('error', 'An error occurred while adjusting stock.');
        }
    }
}
