<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'avatar', 'wallet_balance'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'wallet_balance' => 'decimal:2',
        ];
    }

    /**
     * Public URL for the profile photo, or null when none is set.
     * Resolves the same storage conventions the admin avatar field uses:
     * a bare filename lives in public/uploads/users, an "uploads/…" path is
     * public-relative, and anything else is on the public disk (storage/…).
     */
    public function avatarUrl(): ?string
    {
        $avatar = $this->avatar;

        if (blank($avatar)) {
            return null;
        }

        if (! str_contains($avatar, '/')) {
            return asset('uploads/users/'.$avatar);
        }

        return str_starts_with($avatar, 'uploads/')
            ? asset($avatar)
            : asset('storage/'.$avatar);
    }

    /**
     * @return HasMany<WalletTransaction, $this>
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest('id');
    }

    /**
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->latest('is_default')->latest('id');
    }

    /**
     * Products this customer has saved to their wishlist.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function wishlist(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')
            ->withTimestamps()
            ->latest('wishlists.id');
    }

    /**
     * The customer's persisted (cross-device) shopping cart.
     *
     * @return HasOne<Cart, $this>
     */
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }
}
