<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(
                'role:admin|manager',
                only: ['index', 'edit', 'create', 'destroy']
            ),
            new Middleware(
                'role:admin|manager|staff',
                only: ['index']
            )
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

        $roles = Role::query()
            ->with('permissions')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('permissions', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.roles.index', [
            'roles' => $roles,
            'perPage' => $perPage,
        ]);
    }

    public function create()
    {
        $permissions = Permission::orderBy('id', 'asc')->get();
        return view('admin.roles.create', [
            'permissions' => $permissions,
        ]);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|unique:roles',
        ]);

        if ($validator->passes()) {
            $role = Role::create([
                'name' => $request->name,
            ]);

            if (!empty($request->permissions)) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            }

            return redirect()->route('admin.roles.index')->with('success', 'Role created successfully!');
        } else {
            return redirect()->route('admin.roles.create')->withInput()->withErrors($validator);
        }
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $hasPermissions = $role->permissions()->pluck('name');
        $permissions = Permission::orderBy('id', 'asc')->get();
        return view('admin.roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
            'hasPermissions' => $hasPermissions,
        ]);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|unique:roles,name,' . $role->id,
        ]);

        if ($validator->passes()) {
            $role->update([
                'name' => $request->name,
            ]);

            if (!empty($request->permissions)) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            } else {
                $role->syncPermissions([]);
            }

            return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully!');
        } else {
            return redirect()->route('admin.roles.edit', $role->id)->withInput()->withErrors($validator);
        }
    }

    public function destroy($id)
    {
        $role = Role::find($id);

        if ($role == null) {
            return redirect()->route('admin.roles.index')->with('fail', 'Role not found.');
        }

        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully!');
    }
}
