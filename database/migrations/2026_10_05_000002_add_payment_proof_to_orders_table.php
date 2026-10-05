<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proof of a manual (bank/QR) payment supplied by the customer at checkout:
 * the transfer screenshot and an optional bank transaction reference, so an
 * admin can verify the payment before marking the order paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_reference', 100)->nullable()->after('payment_status');
            $table->string('payment_proof')->nullable()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_reference', 'payment_proof']);
        });
    }
};
