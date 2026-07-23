<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Create/update/delete customer shipping addresses, keeping exactly one
 * default per user.
 */
final class AddressService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data): Address {
            // First address, or an explicit request, becomes the default.
            $makeDefault = ! empty($data['is_default']) || $user->addresses()->count() === 0;
            $data['is_default'] = $makeDefault;

            $address = $user->addresses()->create($data);

            if ($makeDefault) {
                $this->clearOtherDefaults($user, $address->id);
            }

            return $address;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Address $address, array $data): Address
    {
        return DB::transaction(function () use ($address, $data): Address {
            $makeDefault = ! empty($data['is_default']);

            // A plain edit never demotes the current default; only promoting
            // another address (or "set as default") changes the flag.
            if ($makeDefault) {
                $data['is_default'] = true;
            } else {
                unset($data['is_default']);
            }

            $address->update($data);

            if ($makeDefault) {
                $this->clearOtherDefaults($address->user, $address->id);
            }

            return $address;
        });
    }

    public function delete(Address $address): void
    {
        $user = $address->user;
        $wasDefault = $address->is_default;

        $address->delete();

        // Promote another address so the user always keeps a default.
        if ($wasDefault) {
            $user->addresses()->first()?->update(['is_default' => true]);
        }
    }

    public function makeDefault(User $user, Address $address): void
    {
        $address->update(['is_default' => true]);
        $this->clearOtherDefaults($user, $address->id);
    }

    private function clearOtherDefaults(User $user, int $keepId): void
    {
        $user->addresses()->whereKeyNot($keepId)->update(['is_default' => false]);
    }
}
