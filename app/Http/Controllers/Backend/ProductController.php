<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

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

        return view('admin.products.index', [
            'products' => $this->products->paginate($filters, $perPage),
            'perPage' => $perPage,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', $this->products->formData());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request);

        return to_route('admin.products.index')
            ->with('success', "Product \"{$product->name}\" created successfully!");
    }

    public function show(string $id): View
    {
        $product = Product::with([
            'category', 'subCategory', 'brand', 'tags',
            'images', 'specifications', 'variants.values.attribute',
        ])->findOrFail($id);

        return view('admin.products.show', ['product' => $product]);
    }

    public function edit(string $id): View
    {
        $product = Product::with(['images', 'variants.values', 'specifications', 'tags'])->findOrFail($id);

        return view('admin.products.edit', array_merge($this->products->formData(), [
            'product' => $product,
        ]));
    }

    public function update(UpdateProductRequest $request, string $id): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $this->products->update($request, $product);

        return to_route('admin.products.index')
            ->with('success', "Product \"{$product->name}\" updated successfully!");
    }

    public function destroy(string $id): RedirectResponse
    {
        $product = Product::with('images')->findOrFail($id);
        $this->products->delete($product);

        return to_route('admin.products.index')
            ->with('success', 'Product deleted successfully!');
    }

}
