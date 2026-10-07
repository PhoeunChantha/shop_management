<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\WalletException;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * In-house customer wallet (store credit). Balance lives on `users.wallet_balance`;
 * every change is atomic (row-locked) and logged to `wallet_transactions`.
 */
final class WalletService
{
    /** Human labels for wallet_transactions.type. */
    public const TYPES = [
        'topup' => 'Top-up',
        'payment' => 'Order payment',
        'refund' => 'Refund',
        'adjustment' => 'Admin adjustment',
        'loyalty' => 'Loyalty reward',
    ];

    /**
     * Every wallet movement, newest first, for the admin Wallets page.
     * Search matches customer name/email, order number or the note.
     *
     * @param  array{tx_search?: string|null, tx_type?: string|null}  $filters
     */
    public function transactions(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $term = trim((string) ($filters['tx_search'] ?? ''));

        return WalletTransaction::query()
            ->with(['user:id,name,email', 'order:id,order_number'])
            ->when(filled($filters['tx_type'] ?? null), fn ($q) => $q->where('type', $filters['tx_type']))
            ->when($term !== '', function ($q) use ($term): void {
                $q->where(function ($q) use ($term): void {
                    $q->where('description', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))
                        ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$term}%"));
                });
            })
            ->latest('id')
            ->paginate($perPage, ['*'], 'tx_page')
            ->withQueryString();
    }

    public function typeLabel(string $type): string
    {
        return self::TYPES[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    public function balance(User $user): float
    {
        return (float) $user->wallet_balance;
    }

    public function hasSufficient(User $user, float $amount): bool
    {
        return (float) $user->wallet_balance >= round($amount, 2);
    }

    public function credit(User $user, float $amount, string $type, ?string $description = null, ?int $orderId = null): WalletTransaction
    {
        return $this->apply($user, abs($amount), $type, $description, $orderId);
    }

    public function debit(User $user, float $amount, string $type, ?string $description = null, ?int $orderId = null): WalletTransaction
    {
        return $this->apply($user, -abs($amount), $type, $description, $orderId);
    }

    private function apply(User $user, float $delta, string $type, ?string $description, ?int $orderId): WalletTransaction
    {
        return DB::transaction(function () use ($user, $delta, $type, $description, $orderId): WalletTransaction {
            // Lock the row so concurrent debits/credits can't race the balance.
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $new = round((float) $locked->wallet_balance + $delta, 2);

            if ($new < 0) {
                throw new WalletException('Insufficient wallet balance.');
            }

            $locked->forceFill(['wallet_balance' => $new])->save();
            $user->setAttribute('wallet_balance', $new);

            return WalletTransaction::create([
                'user_id' => $locked->id,
                'type' => $type,
                'amount' => round($delta, 2),
                'balance_after' => $new,
                'description' => $description,
                'order_id' => $orderId,
            ]);
        });
    }
}
