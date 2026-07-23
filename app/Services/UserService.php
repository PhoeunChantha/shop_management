<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

final class UserService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, function ($query, string $role): void {
                $query->whereHas('roles', fn ($query) => $query->where('name', $role));
            })
            ->when($filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function roles(string $orderBy = 'name'): \Illuminate\Database\Eloquent\Collection
    {
        return Role::orderBy($orderBy)->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $avatar = null): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'avatar' => $avatar ? $this->storeAvatar($avatar) : null,
        ]);

        $user->syncRoles([$data['role']]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        $user->name = $data['name'];
        $user->email = $data['email'];

        if ($avatar) {
            $this->deleteAvatar($user->avatar);
            $user->avatar = $this->storeAvatar($avatar);
        }

        $user->save();
        $user->syncRoles([$data['role']]);

        return $user;
    }

    public function delete(User $user): void
    {
        $user->roles()->detach();
        $this->deleteAvatar($user->avatar);
        $user->delete();
    }

    private function storeAvatar(UploadedFile $file): string
    {
        $directory = 'uploads/users';
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
