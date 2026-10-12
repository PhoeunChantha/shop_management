<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per QR payment created at CutLuy, for an order or a wallet top-up.
        Schema::create('cutluy_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_id')->unique();            // CutLuy's id
            $table->morphs('payable');                         // Order | WalletTopup
            $table->string('reference_id')->index();           // order number / top-up tran_id
            $table->string('idempotency_key')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending');      // pending, scanned, paid, expired, failed
            $table->string('checkout_url')->nullable();
            $table->text('qr_string')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();               // last payment object seen from CutLuy
            $table->timestamps();
        });

        // Webhook deliveries, so a redelivered event is handled once.
        Schema::create('cutluy_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event');
            $table->string('payment_id');
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['payment_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutluy_webhook_events');
        Schema::dropIfExists('cutluy_payments');
    }
};
