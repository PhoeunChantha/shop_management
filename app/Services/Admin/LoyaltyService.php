<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Credits a configurable % of a paid order's grand total back to the
 * customer's wallet as a loyalty reward (Settings → Loyalty Points).
 */
final class LoyaltyService
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Award loyalty points for a paid order. Safe to call from every payment
     * path (admin status update, wallet checkout, PayWay confirm) and more
     * than once — only ever credits once per order, guarded by
     * `orders.loyalty_credited_at`.
     */
    public function awardForOrder(Order $order): void
    {
        if ($order->user_id === null || $order->loyalty_credited_at !== null) {
            return;
        }

        $config = $this->settings->loyalty();

        if (! $config['enabled'] || $config['earn_rate'] <= 0) {
            return;
        }

        DB::transaction(function () use ($order, $config): void {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked || $locked->user_id === null || $locked->loyalty_credited_at !== null) {
                return;
            }

            $amount = round((float) $locked->grand_total * $config['earn_rate'] / 100, 2);

            if ($amount <= 0) {
                return;
            }

            $this->wallet->credit(
                $locked->user,
                $amount,
                'loyalty',
                __('Loyalty reward for order :number', ['number' => $locked->order_number]),
                $locked->id,
            );

            $locked->forceFill(['loyalty_credited_at' => now()])->save();
        });
    }
}
