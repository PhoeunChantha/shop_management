<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Admin\AbandonedCartService;
use Illuminate\Console\Command;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'shop:send-abandoned-cart-reminders';

    protected $description = 'Email a one-time recovery reminder to customers who abandoned their cart.';

    public function handle(AbandonedCartService $service): int
    {
        $count = $service->sendReminders();

        $this->info("Sent {$count} abandoned-cart reminder(s).");

        return self::SUCCESS;
    }
}
