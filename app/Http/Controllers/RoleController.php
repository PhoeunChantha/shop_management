<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

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

    public function index()
    {
        $roles = Role::orderBy('id', 'asc')->paginate(10);
        return view('roles.index', [
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        $permissions = Permission::orderBy('id', 'asc')->get();
        return view('roles.create', [
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

            return redirect()->route('roles.index')->with('success', 'Role created successfully!');
        } else {
            return redirect()->route('roles.create')->withInput()->withErrors($validator);
        }
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $hasPermissions = $role->permissions()->pluck('name');
        $permissions = Permission::orderBy('id', 'asc')->get();
        return view('roles.edit', [
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

            return redirect()->route('roles.index')->with('success', 'Role updated successfully!');
        } else {
            return redirect()->route('roles.edit', $role->id)->withInput()->withErrors($validator);
        }
    }

    public function destroy($id)
    {
        $role = Role::find($id);

        if ($role == null) {
            return redirect()->route('roles.index')->with('fail', 'Role not found.');
        }

        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role deleted successfully!');
    }
}
