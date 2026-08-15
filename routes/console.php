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
