<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Color;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ColorController extends Controller implements HasMiddleware
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

        $colors = Color::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.colors.index', [
            'colors'  => $colors,
            'perPage' => $perPage,
        ]);
    }

    public function create()
    {
        return view('admin.colors.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'code'       => 'required|string|max:50|unique:colors,code', // e.g., #FF0000, #000000
            'sort_order' => 'nullable|integer',
            'status'     => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.colors.create')
                ->withErrors($validator)
                ->withInput();
        }

        $color = new Color();
        $color->name       = $request->name;
        $color->code       = $request->code;
        $color->hex_code   = $request->code;
        $color->sort_order = $request->sort_order ?? 0;
        $color->status     = $request->status;
        $color->save();

        return redirect()->route('admin.colors.index')
            ->with('success', 'Color created successfully!');
    }

    public function edit(string $id)
    {
        $color = Color::findOrFail($id);

        return view('admin.colors.edit', [
            'color' => $color,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $color = Color::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'code'       => 'required|string|max:50|unique:colors,code,' . $id,
            'sort_order' => 'nullable|integer',
            'status'     => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.colors.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $color->name       = $request->name;
        $color->code       = $request->code;
        $color->hex_code   = $request->code; 
        $color->sort_order = $request->sort_order ?? 0;
        $color->status     = $request->status;
        $color->save();

        return redirect()->route('admin.colors.index')
            ->with('success', 'Color updated successfully!');
    }

    public function destroy(string $id)
    {
        $color = Color::findOrFail($id);
        $color->delete();

        return redirect()->route('admin.colors.index')
            ->with('success', 'Color deleted successfully!');
    }
}
