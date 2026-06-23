<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class UserController extends Controller implements HasMiddleware
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
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = trim($filters['search'] ?? '');

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['role']), function ($query) use ($filters) {
                $query->whereHas('roles', function ($query) use ($filters) {
                    $query->where('name', $filters['role']);
                });
            })
            ->when(! empty($filters['date_from']), function ($query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when(! empty($filters['date_to']), function ($query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            })
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        $roles = Role::orderBy('name', 'asc')->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'perPage' => $perPage,
        ]);
    }

    public function create()
    {
        $roles = Role::orderBy('name', 'asc')->get();
        return view('admin.users.create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:5|same:confirm_password',
            'confirm_password' => 'required',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'role' => 'required|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return redirect()->route('users.create')
                ->withErrors($validator)
                ->withInput();
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = $request->password;

        if ($request->hasFile('avatar')) {
            $user->avatar = $this->storeAvatar($request);
        }

        $user->save();

        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully!');
    }

    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        $roles = Role::orderBy('id', 'asc')->get();
        $hasRoles = $user->roles->pluck('name');

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $roles,
            'hasRoles' => $hasRoles,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|exists:roles,name',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->route('users.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->hasFile('avatar')) {
            $this->deleteAvatar($user->avatar);
            $user->avatar = $this->storeAvatar($request);
        }

        $user->save();

        $user->syncRoles([$request->role]);
        return redirect()->route('users.index')
            ->with('success', 'User update success!!');
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        if (Auth::id() === $user->id) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account!');
        }

        $user->roles()->detach();
        $this->deleteAvatar($user->avatar);
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully!');
    }

    private function storeAvatar(Request $request): string
    {
        $directory = 'uploads/users';
        $file = $request->file('avatar');
        $filename = $file->hashName();

        File::ensureDirectoryExists(public_path($directory));
        $file->move(public_path($directory), $filename);

        return $filename;
    }

    private function deleteAvatar(?string $avatar): void
    {
        if (! $avatar) {
            return;
        }

        if (str_contains($avatar, '/') && str_starts_with($avatar, 'uploads/')) {
            File::delete(public_path($avatar));
            return;
        }

        if (! str_contains($avatar, '/')) {
            File::delete(public_path("uploads/users/{$avatar}"));
            return;
        }

        if (Storage::disk('public')->exists($avatar)) {
            Storage::disk('public')->delete($avatar);
        }
    }
}
