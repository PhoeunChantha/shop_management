<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Admin\OrderService;
use App\Services\Admin\SettingService;
use Illuminate\Console\Command;

class CancelUnpaidOrders extends Command
{
    protected $signature = 'shop:cancel-unpaid-orders';

    protected $description = 'Cancel pending orders left unpaid past the limit in Settings → Checkout & Security, returning their stock.';

    public function handle(OrderService $orders, SettingService $settings): int
    {
        $hours = $settings->checkoutSecurity()['unpaid_expiry_hours'];

        if ($hours === 0) {
            $this->info('Unpaid-order expiry is off.');

            return self::SUCCESS;
        }

        $count = $orders->cancelExpiredUnpaid($hours);

        $this->info("Cancelled {$count} order(s) unpaid for more than {$hours} hour(s).");

        return self::SUCCESS;
    }
}
