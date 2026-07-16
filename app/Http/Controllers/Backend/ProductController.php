<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ProductsExport;
use App\Exports\ProductTemplateExport;
use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Imports\ProductsImport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    use HandlesBulkActions;

    public function __construct(
        private readonly ProductService $products,
        private readonly SettingService $settings,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

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
        $this->authorize('create', Product::class);

        return view('admin.products.create', $this->products->formData());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $product = $this->products->create($request);

        return to_route('admin.products.index')
            ->with('success', "Product \"{$product->name}\" created successfully!");
    }

    public function show(string $id): View
    {
        $this->authorize('view', Product::class);

        $product = Product::with([
            'category', 'subCategory', 'brand', 'tags',
            'images', 'specifications', 'variants.values.attribute',
        ])->findOrFail($id);

        return view('admin.products.show', ['product' => $product]);
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Product::class);

        $product = Product::with(['images', 'variants.values', 'specifications', 'tags'])->findOrFail($id);

        return view('admin.products.edit', array_merge($this->products->formData(), [
            'product' => $product,
        ]));
    }

    public function update(UpdateProductRequest $request, string $id): RedirectResponse
    {
        $this->authorize('update', Product::class);

        $product = Product::findOrFail($id);
        $this->products->update($request, $product);

        return to_route('admin.products.index')
            ->with('success', "Product \"{$product->name}\" updated successfully!");
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', Product::class);

        $product = Product::with('images')->findOrFail($id);
        $this->products->delete($product);

        return to_route('admin.products.index')
            ->with('success', 'Product deleted successfully!');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorize('delete', Product::class);

        $ids = $this->validatedIds($request);

        // Delegate to the service per product so images + variants are cleaned up.
        $products = Product::with('images')->whereKey($ids)->get();
        $products->each(fn (Product $product) => $this->products->delete($product));

        return back()->with('success', $products->count().' product(s) deleted successfully!');
    }

    public function bulkStatus(Request $request): RedirectResponse
    {
        $this->authorize('update', Product::class);

        [$ids, $status] = $this->validatedStatus($request);

        // Product status is a string enum — map Enable/Disable to active/inactive.
        $value = $status ? 'active' : 'inactive';
        $count = Product::whereKey($ids)->update(['status' => $value]);

        return back()->with('success', $count.' product(s) '.($status ? 'activated' : 'deactivated').'.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $this->authorize('update', Product::class);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
            'operation' => ['required', Rule::in(['status', 'category', 'brand', 'flag'])],
            'status' => ['nullable', 'required_if:operation,status', Rule::in(['draft', 'active', 'inactive', 'archived'])],
            'category_id' => ['nullable', 'required_if:operation,category', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'required_if:operation,brand', 'integer', 'exists:brands,id'],
            'flag' => ['nullable', 'required_if:operation,flag', Rule::in(['is_featured', 'is_new', 'is_best_seller', 'is_on_sale'])],
            'flag_value' => ['nullable', 'required_if:operation,flag', 'boolean'],
        ]);

        $payload = match ($data['operation']) {
            'status' => ['status' => $data['status']],
            'category' => ['category_id' => $data['category_id']],
            'brand' => ['brand_id' => $data['brand_id']],
            'flag' => [$data['flag'] => (bool) $data['flag_value']],
        };

        $count = Product::whereKey($data['ids'])->update($payload);

        return back()->with('success', $count.' product(s) updated.');
    }

    public function bulkExport(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Product::class);

        $ids = $this->validatedIds($request);
        $languages = array_keys($this->settings->activeLanguages());
        $export = new ProductsExport(
            $this->products,
            ['ids' => $ids],
            $languages,
            $this->settings->primaryLanguage(),
        );

        return Excel::download($export, 'selected-products-'.now()->format('Y-m-d_His').'.xlsx');
    }

    /* ---------------- Import / Export ---------------- */

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:draft,active,inactive,archived'],
            'stock' => ['nullable', 'in:in_stock,out_of_stock,low_stock'],
            'flag' => ['nullable', 'in:featured,new,best_seller,on_sale'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $languages = array_keys($this->settings->activeLanguages());
        $export = new ProductsExport($this->products, $filters, $languages, $this->settings->primaryLanguage());

        return Excel::download($export, 'products-'.now()->format('Y-m-d_His').'.xlsx');
    }

    public function template(): BinaryFileResponse
    {
        $this->authorize('create', Product::class);

        $export = new ProductTemplateExport(
            array_keys($this->settings->activeLanguages()),
            Category::orderBy('name')->pluck('name')->all(),
            Brand::orderBy('name')->pluck('name')->all(),
        );

        return Excel::download($export, 'product-import-template.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $import = new ProductsImport($this->settings);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not read the file. Make sure it matches the template.');
        }

        $redirect = back()->with('success', "Import finished — {$import->created} created, {$import->updated} updated.");

        if (! empty($import->errors)) {
            $redirect->with('warning', count($import->errors).' row(s) were skipped. See the details below.');
            session()->flash('import_errors', $import->errors);
        }

        return $redirect;
    }
}
