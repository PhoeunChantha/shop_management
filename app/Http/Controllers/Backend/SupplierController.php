<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(private readonly SupplierService $suppliers) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('view suppliers'), 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:0,1'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
        $perPage = (int) ($filters['per_page'] ?? 10);

        return view('admin.suppliers.index', [
            'suppliers' => $this->suppliers->paginate($filters, $perPage),
            'stats' => $this->suppliers->stats(),
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('create suppliers'), 403);

        return view('admin.suppliers.create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = $this->suppliers->create($request->validated() + ['status' => $request->boolean('status', true)]);

        return to_route('admin.suppliers.index')->with('success', "Supplier {$supplier->name} created.");
    }

    public function edit(Request $request, Supplier $supplier): View
    {
        abort_unless($request->user()->hasPermissionTo('edit suppliers'), 403);

        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->suppliers->update($supplier, $request->validated() + ['status' => $request->boolean('status')]);

        return to_route('admin.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('delete suppliers'), 403);

        try {
            $this->suppliers->delete($supplier);
            return back()->with('success', 'Supplier deleted.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
