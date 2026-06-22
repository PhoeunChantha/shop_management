<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

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
    public function index()
    {
        $permissions = Permission::orderBy('created_at', 'asc')->paginate(10);
        return view('permissions.index', [
            'permissions' => $permissions,
        ]);
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required|string|min:3|unique:permissions',
        ]);

        if ($validator->passes()) {
           Permission::create([
                'name' => $request->name,
            ]);

            return redirect()->route('permissions.index')->with('success', 'Permission created successfully!');
        } else {
            return redirect()->route('permissions.create')->withInput()->withErrors($validator);
        }
    }

    public function edit($id)
    {
        $permission = Permission::findOrFail($id);
        return view('permissions.edit', [
            'permission' => $permission,
        ]);
    }

    public function update($id, Request $request)
    {
        $permission = Permission::findOrFail($id);
        $validator = Validator::make($request->all(),[
            'name' => 'required|string|min:3|unique:permissions,name,'.$id.'id',
        ]);

        if ($validator->passes()) {
           $permission->name = $request->name;
           $permission->save();

            return redirect()->route('permissions.index')->with('success', 'Permission updated successfully!');
        } else {
            return redirect()->route('permissions.edit', $id)->withInput()->withErrors($validator);
        }
    }

    public function destroy($id, Request $request)
    {
         $permission = Permission::findOrFail($id);

        $permission->delete();

        return redirect()->route('permissions.index')->with('success', 'Permission deleted successfully!');
    }
}
