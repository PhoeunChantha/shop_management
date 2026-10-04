<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hourly one-time recovery email for idle abandoned carts (idempotent via
// reminder_sent_at). Requires the scheduler (php artisan schedule:run) in cron.
Schedule::command('shop:send-abandoned-cart-reminders')
    ->hourly()
    ->withoutOverlapping();

// Hourly: cancel orders left unpaid past Settings → Checkout & Security →
// "Cancel unpaid orders after", returning their stock (stops fake orders
// from holding inventory). Requires the scheduler in cron.
Schedule::command('shop:cancel-unpaid-orders')
    ->hourly()
    ->withoutOverlapping();
