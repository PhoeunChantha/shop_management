<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Size;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SizeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(
                'role:admin|manager',
                only: ['index', 'edit', 'create', 'update', 'store', 'destroy']
            ),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search'   => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search  = trim($filters['search'] ?? '');

        $sizes = Size::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order', 'asc') 
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.sizes.index', [
            'sizes'   => $sizes,
            'perPage' => $perPage,
        ]);
    }

    public function create()
    {
        return view('admin.sizes.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'code'       => 'required|string|max:50|unique:sizes,code', // e.g., S, M, L, XL
            'sort_order' => 'nullable|integer',
            'status'     => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.sizes.create')
                ->withErrors($validator)
                ->withInput();
        }

        $size = new Size();
        $size->name       = $request->name;
        $size->code       = strtoupper($request->code); // បំប្លែងជាអក្សរធំស្វ័យប្រវត្ត (e.g., xl -> XL)
        $size->sort_order = $request->sort_order ?? 0;
        $size->status     = $request->status;
        $size->save();

        return redirect()->route('admin.sizes.index')
            ->with('success', 'Size created successfully!');
    }

    public function edit(string $id)
    {
        $size = Size::findOrFail($id);

        return view('admin.sizes.edit', [
            'size' => $size,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $size = Size::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'code'       => 'required|string|max:50|unique:sizes,code,' . $id,
            'sort_order' => 'nullable|integer',
            'status'     => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.sizes.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $size->name       = $request->name;
        $size->code       = strtoupper($request->code);
        $size->sort_order = $request->sort_order ?? 0;
        $size->status     = $request->status;
        $size->save();

        return redirect()->route('admin.sizes.index')
            ->with('success', 'Size updated successfully!');
    }

    public function destroy(string $id)
    {
        $size = Size::findOrFail($id);
        $size->delete();

        return redirect()->route('admin.sizes.index')
            ->with('success', 'Size deleted successfully!');
    }
}