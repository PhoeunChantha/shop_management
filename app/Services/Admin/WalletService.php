<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\WalletException;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * In-house customer wallet (store credit). Balance lives on `users.wallet_balance`;
 * every change is atomic (row-locked) and logged to `wallet_transactions`.
 */
final class WalletService
{
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
