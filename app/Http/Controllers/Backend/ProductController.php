<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ProductTemplateExport;
use App\Exports\ProductsExport;
use App\Http\Controllers\Backend\Concerns\HandlesBulkActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImportService;
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
        private readonly ProductImportService $imports,
        private readonly SettingService $settings,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->validate($this->filterRules(withPerPage: true));
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

        return view('admin.products.show', ['product' => $this->products->findForShow($id)]);
    }

    public function edit(string $id): View
    {
        $this->authorize('update', Product::class);

        return view('admin.products.edit', array_merge($this->products->formData(), [
            'product' => $this->products->findForEdit($id),
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

        $this->products->delete(Product::with('images')->findOrFail($id));

        return to_route('admin.products.index')
            ->with('success', 'Product deleted successfully!');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorize('delete', Product::class);

        $count = $this->products->bulkDelete($this->validatedIds($request));

        return back()->with('success', $count.' product(s) deleted successfully!');
    }

    public function bulkStatus(Request $request): RedirectResponse
    {
        $this->authorize('update', Product::class);

        [$ids, $status] = $this->validatedStatus($request);
        $count = $this->products->setStatus($ids, $status);

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

        $count = $this->products->bulkUpdate($data);

        return back()->with('success', $count.' product(s) updated.');
    }

    public function bulkExport(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Product::class);

        return Excel::download(
            new ProductsExport(
                $this->products,
                ['ids' => $this->validatedIds($request)],
                array_keys($this->settings->activeLanguages()),
                $this->settings->primaryLanguage(),
            ),
            'selected-products-'.now()->format('Y-m-d_His').'.xlsx',
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->validate($this->filterRules());

        return Excel::download(
            new ProductsExport(
                $this->products,
                $filters,
                array_keys($this->settings->activeLanguages()),
                $this->settings->primaryLanguage(),
            ),
            'products-'.now()->format('Y-m-d_His').'.xlsx',
        );
    }

    public function template(): BinaryFileResponse
    {
        $this->authorize('create', Product::class);

        return Excel::download(
            new ProductTemplateExport(
                array_keys($this->settings->activeLanguages()),
                Category::orderBy('name')->pluck('name')->all(),
                Brand::orderBy('name')->pluck('name')->all(),
            ),
            'product-import-template.xlsx',
        );
    }

    public function importPreview(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240']]);
        $result = $this->imports->preview($request->file('file'), $request->session());

        return back()->with($result['status'], $result['message']);
    }

    public function confirmImport(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $result = $this->imports->confirm($request->session());
        $redirect = back()->with($result['status'], $result['message']);

        if (! empty($result['errors'])) {
            $redirect->with('warning', count($result['errors']).' row(s) were skipped. See the details below.');
            session()->flash('import_errors', $result['errors']);
        }

        return $redirect;
    }

    public function cancelImport(Request $request): RedirectResponse
    {
        $this->imports->cancel($request->session());

        return back()->with('info', 'Product import preview cancelled.');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240']]);
        $result = $this->imports->import($request->file('file'));
        $redirect = back()->with($result['status'], $result['message']);

        if (! empty($result['errors'])) {
            $redirect->with('warning', count($result['errors']).' row(s) were skipped. See the details below.');
            session()->flash('import_errors', $result['errors']);
        }

        return $redirect;
    }

    /**
     * @return array<string, mixed>
     */
    private function filterRules(bool $withPerPage = false): array
    {
        return array_filter([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:draft,active,inactive,archived'],
            'stock' => ['nullable', 'in:in_stock,out_of_stock,low_stock'],
            'flag' => ['nullable', 'in:featured,new,best_seller,on_sale'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'per_page' => $withPerPage ? ['nullable', 'integer', 'in:5,10,25,50'] : null,
        ]);
    }
}
