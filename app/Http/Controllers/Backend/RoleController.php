<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roles,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        return view('admin.roles.index', [
            'roles' => $this->roles->paginate($filters, $perPage),
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'permissions' => $this->roles->permissions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $this->roles->create($data);

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully!');
    }

    public function edit($id): View
    {
        $role = Role::findOrFail($id);
        $hasPermissions = $role->permissions()->pluck('name');

        return view('admin.roles.edit', [
            'role' => $role,
            'permissions' => $this->roles->permissions(),
            'hasPermissions' => $hasPermissions,
        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'unique:roles,name,'.$role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $this->roles->update($role, $data);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully!');
    }

    public function destroy($id): RedirectResponse
    {
        $role = Role::find($id);

        if ($role == null) {
            return redirect()->route('admin.roles.index')->with('fail', 'Role not found.');
        }

        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully!');
    }
}
