<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ImageManager;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(
                'role:admin|manager',
                only: ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']
            ),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? '';

        $products = Product::query()
            ->with('category')
            ->withCount('variants')
            ->withSum('variants', 'stock')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return redirect()->route('admin.products.create')
                ->withErrors($validator)
                ->withInput();
        }

        DB::transaction(function () use ($request) {
            $product = new Product;
            $this->fillProduct($product, $request);
            $product->slug = $this->uniqueSlug($request->name);
            $product->save();

            $this->storeImages($product, $request);
            $this->syncVariants($product, $request);
        });

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully!');
    }

    public function show(string $id): View
    {
        $product = Product::with(['category', 'subCategory', 'images', 'variants.size', 'variants.color'])
            ->findOrFail($id);

        return view('admin.products.show', ['product' => $product]);
    }

    public function edit(string $id): View
    {
        $product = Product::with(['images', 'variants'])->findOrFail($id);

        return view('admin.products.edit', array_merge($this->formData(), [
            'product' => $product,
        ]));
    }

    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validator = $this->validator($request, $product->id);

        if ($validator->fails()) {
            return redirect()->route('admin.products.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        DB::transaction(function () use ($request, $product) {
            $this->fillProduct($product, $request);
            $product->slug = $this->uniqueSlug($request->name, $product->id);
            $product->save();

            $this->removeImages($product, $request);
            $this->storeImages($product, $request);
            $this->syncVariants($product, $request);
        });

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully!');
    }

    public function destroy(string $id)
    {
        $product = Product::with('images')->findOrFail($id);

        DB::transaction(function () use ($product) {
            // Remove gallery files from disk (rows cascade on delete).
            foreach ($product->images as $image) {
                ImageManager::delete($image->image, 'products');
            }

            $product->delete();
        });

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully!');
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Shared dropdown data for create/edit forms.
     */
    private function formData(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'sizes' => Size::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code']),
            'colors' => Color::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'hex_code']),
        ];
    }

    /**
     * Validate a product payload (including nested images + variants), with
     * SKU uniqueness that ignores the product being updated.
     */
    private function validator(Request $request, ?int $ignoreId = null): \Illuminate\Validation\Validator
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|min:2|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',

            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'removed_images' => 'nullable|array',
            'removed_images.*' => 'integer',

            'variants' => 'nullable|array',
            'variants.*.size_id' => 'required|exists:sizes,id',
            'variants.*.color_id' => 'required|exists:colors,id',
            'variants.*.sku' => 'required|string|max:100|distinct',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.price' => 'nullable|numeric|min:0',
        ], [
            'variants.*.size_id.required' => 'Select a size for each variant.',
            'variants.*.color_id.required' => 'Select a color for each variant.',
            'variants.*.sku.required' => 'SKU is required for each variant.',
            'variants.*.sku.distinct' => 'Duplicate SKU in the variant list.',
            'variants.*.stock.required' => 'Stock is required for each variant.',
        ]);

        $validator->after(function ($validator) use ($request, $ignoreId) {
            // Percentage discount cannot exceed 100%.
            if ($request->discount_type === 'percentage' && (float) $request->discount_amount > 100) {
                $validator->errors()->add('discount_amount', 'Percentage discount cannot exceed 100%.');
            }

            // Prevent SKUs that already exist on OTHER products.
            foreach ((array) $request->input('variants', []) as $i => $variant) {
                $sku = trim($variant['sku'] ?? '');

                if ($sku === '') {
                    continue;
                }

                $exists = DB::table('product_variants')
                    ->where('sku', $sku)
                    ->when($ignoreId, fn ($q) => $q->where('product_id', '!=', $ignoreId))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add("variants.{$i}.sku", "SKU \"{$sku}\" is already in use.");
                }
            }
        });

        return $validator;
    }

    private function fillProduct(Product $product, Request $request): void
    {
        $product->category_id = $request->category_id;
        $product->sub_category_id = $request->sub_category_id ?: null;
        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->discount_type = $request->discount_type ?: null;
        $product->discount_amount = $request->filled('discount_type') ? ($request->discount_amount ?? 0) : 0;
        $product->status = $request->status;
    }

    private function storeImages(Product $product, Request $request): void
    {
        foreach ((array) $request->file('images', []) as $file) {
            $product->images()->create([
                'image' => ImageManager::upload($file, 'products'),
            ]);
        }
    }

    private function removeImages(Product $product, Request $request): void
    {
        $ids = array_filter((array) $request->input('removed_images', []));

        if (empty($ids)) {
            return;
        }

        $images = ProductImage::where('product_id', $product->id)->whereIn('id', $ids)->get();

        foreach ($images as $image) {
            ImageManager::delete($image->image, 'products');
            $image->delete();
        }
    }

    /**
     * Replace the product's variants with the submitted set (add/update/remove
     * in one pass). Empty rows are skipped.
     */
    private function syncVariants(Product $product, Request $request): void
    {
        $product->variants()->delete();

        foreach ((array) $request->input('variants', []) as $variant) {
            if (empty($variant['sku']) || empty($variant['size_id']) || empty($variant['color_id'])) {
                continue;
            }

            $product->variants()->create([
                'size_id' => $variant['size_id'],
                'color_id' => $variant['color_id'],
                'sku' => trim($variant['sku']),
                'stock' => $variant['stock'] ?? 0,
                'price' => ($variant['price'] ?? '') === '' ? null : $variant['price'],
            ]);
        }
    }

    /**
     * URL-safe slug, guaranteed unique on the products table.
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (
            Product::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
