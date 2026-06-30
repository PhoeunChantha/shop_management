<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductTag;
use App\Models\Size;
use App\Services\ProductService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ProductController extends Controller implements HasMiddleware
{
    public function __construct(private readonly ProductService $products) {}

    public static function middleware(): array
    {
        return [
            new Middleware('role:admin|manager'),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:draft,active,inactive,archived'],
            'stock' => ['nullable', 'in:in_stock,out_of_stock,low_stock'],
            'flag' => ['nullable', 'in:featured,new,best_seller,on_sale'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $products = Product::query()
            ->with(['category', 'brand', 'images'])
            ->withCount('variants')
            ->withSum('variants', 'stock')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('variants', function ($q) use ($search) {
                            $q->where('sku', 'like', "%{$search}%")
                                ->orWhere('barcode', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['brand_id'] ?? null, fn ($q, $v) => $q->where('brand_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(($filters['min_price'] ?? null) !== null, fn ($q) => $q->where('price', '>=', $filters['min_price']))
            ->when(($filters['max_price'] ?? null) !== null, fn ($q) => $q->where('price', '<=', $filters['max_price']))
            ->when(($filters['stock'] ?? null) === 'in_stock', fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('stock', '>', 0)))
            ->when(($filters['stock'] ?? null) === 'out_of_stock', fn ($q) => $q->whereDoesntHave('variants', fn ($v) => $v->where('stock', '>', 0)))
            ->when(($filters['stock'] ?? null) === 'low_stock', fn ($q) => $q->whereHas('variants', fn ($v) => $v->whereColumn('stock', '<=', 'low_stock_alert')->where('low_stock_alert', '>', 0)))
            ->when(($filters['flag'] ?? null) === 'featured', fn ($q) => $q->where('is_featured', true))
            ->when(($filters['flag'] ?? null) === 'new', fn ($q) => $q->where('is_new', true))
            ->when(($filters['flag'] ?? null) === 'best_seller', fn ($q) => $q->where('is_best_seller', true))
            ->when(($filters['flag'] ?? null) === 'on_sale', fn ($q) => $q->where('is_on_sale', true))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'perPage' => $perPage,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', $this->formData());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request);

        return redirect()
            ->route('admin.products.index')
            ->with('success', "Product \"{$product->name}\" created successfully!");
    }

    public function show(string $id): View
    {
        $product = Product::with([
            'category', 'subCategory', 'brand', 'tags',
            'images', 'specifications', 'variants.size', 'variants.color',
        ])->findOrFail($id);

        return view('admin.products.show', ['product' => $product]);
    }

    public function edit(string $id): View
    {
        $product = Product::with(['images', 'variants', 'specifications', 'tags'])->findOrFail($id);

        return view('admin.products.edit', array_merge($this->formData(), [
            'product' => $product,
        ]));
    }

    public function update(UpdateProductRequest $request, string $id): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $this->products->update($request, $product);

        return redirect()
            ->route('admin.products.index')
            ->with('success', "Product \"{$product->name}\" updated successfully!");
    }

    public function destroy(string $id): RedirectResponse
    {
        $product = Product::with('images')->findOrFail($id);
        $this->products->delete($product);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully!');
    }

    /**
     * Quick status change from the list / detail view.
     */
    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:draft,active,inactive,archived'],
        ]);

        $product = Product::findOrFail($id);
        $this->products->updateStatus($product, $data['status']);

        return back()->with('success', 'Product status updated.');
    }

    /**
     * Delete a single gallery image (used from the edit form).
     */
    public function destroyImage(string $image): RedirectResponse
    {
        $this->products->deleteImage(ProductImage::findOrFail($image));

        return back()->with('success', 'Image removed.');
    }

    /**
     * Shared dropdown data for create/edit forms.
     */
    private function formData(): array
    {
        $settings = app(SettingService::class);

        return [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'sizes' => Size::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code']),
            'colors' => Color::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'hex_code']),
            'tags' => ProductTag::orderBy('name')->get(['id', 'name']),
            'locales' => $settings->activeLanguages(),   // code => label
            'primaryLang' => $settings->primaryLanguage(),
        ];
    }
}
