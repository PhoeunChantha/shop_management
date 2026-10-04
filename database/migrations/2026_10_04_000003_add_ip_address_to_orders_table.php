<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The IP an order was placed from — used to cap open unpaid orders per
 * visitor (anti-spam) and to investigate suspicious orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('customer_phone');
            $table->index(['ip_address', 'payment_status']);
            $table->index(['customer_email', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['ip_address', 'payment_status']);
            $table->dropIndex(['customer_email', 'payment_status']);
            $table->dropColumn('ip_address');
        });
    }
};
