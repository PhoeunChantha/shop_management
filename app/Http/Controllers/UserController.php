<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use GrahamCampbell\ResultType\Success;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

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

    public function index()
    {
        $users = User::orderBy('id', 'asc')->paginate(10);
        return view('users.index', [
            'users' => $users,
        ]);
    }

    public function create()
    {
        $roles = Role::orderBy('name', 'asc')->get();
        return view('users.create', [
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
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
        }

        $user->save();

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return redirect()->route('users.index')
            ->with('success', 'User created successfully!');
    }

    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        $roles = Role::orderBy('id', 'asc')->get();
        $hasRoles = $user->roles->pluck('name');

        return view('users.edit', [
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
            'roles' => 'required|array',
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
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
        }

        $user->save();

        $user->syncRoles($request->roles);
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
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully!');
    }
}
