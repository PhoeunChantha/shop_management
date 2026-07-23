<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'in:5,10,25,50'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        return view('admin.users.index', [
            'users' => $this->users->paginate($filters, $perPage),
            'roles' => $this->users->roles(),
            'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'roles' => $this->users->roles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:5', 'same:confirm_password'],
            'confirm_password' => ['required'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'role' => ['required', 'exists:roles,name'],
        ]);

        $this->users->create($data, $request->file('avatar'));

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully!');
    }

    public function edit(string $id): View
    {
        $this->authorize('update', User::class);

        $user = User::with('roles')->findOrFail($id);
        $hasRoles = $user->roles->pluck('name');

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $this->users->roles('id'),
            'hasRoles' => $hasRoles,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $this->authorize('update', User::class);

        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$id],
            'role' => ['required', 'exists:roles,name'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $this->users->update($user, $data, $request->file('avatar'));

        return redirect()->route('admin.users.index')
            ->with('success', 'User update success!!');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('delete', User::class);

        $user = User::findOrFail($id);

        if (Auth::id() === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account!');
        }

        $this->users->delete($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }
}
