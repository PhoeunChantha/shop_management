<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view permission', only: ['index']),
            new Middleware('permission:edit permission', only: ['edit']),
            new Middleware('permission:create permission', only: ['create']),
            new Middleware('permission:delete permission', only: ['destroy']),

        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $permissions = Permission::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.permissions.index', [
            'permissions' => $permissions,
            'perPage' => $perPage,
        ]);
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'names' => 'required|array|min:1',
            'names.*' => 'required|string|min:3|distinct|unique:permissions,name',
        ]);

        if ($validator->passes()) {
            foreach ($request->names as $name) {
                Permission::create([
                    'name' => $name,
                ]);
            }

            return redirect()->route('admin.permissions.index')->with('success', 'Permission created successfully!');
        } else {
            return redirect()->route('admin.permissions.create')->withInput()->withErrors($validator);
        }
    }

    public function edit($id)
    {
        $permission = Permission::findOrFail($id);

        return view('admin.permissions.edit', [
            'permission' => $permission,
        ]);
    }

    public function update($id, Request $request)
    {
        $permission = Permission::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|unique:permissions,name,'.$id.'id',
        ]);

        if ($validator->passes()) {
            $permission->name = $request->name;
            $permission->save();

            
            return redirect()->route('admin.permissions.index')->with('success', 'Permission updated successfully!');
        } else {
            return redirect()->route('admin.permissions.edit', $id)->withInput()->withErrors($validator);
        }
    }

    public function destroy($id, Request $request)
    {
        $permission = Permission::findOrFail($id);


        $permission->delete();

        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted successfully!');
    }
}
