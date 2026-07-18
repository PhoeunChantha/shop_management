<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('fulfillment_status')->default('unfulfilled')->after('status')->index();
            $table->string('carrier')->nullable()->after('shipping_method');
            $table->timestamp('shipped_at')->nullable()->after('tracking_number');
            $table->timestamp('fulfilled_at')->nullable()->after('shipped_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['fulfillment_status']);
            $table->dropColumn(['fulfillment_status', 'carrier', 'shipped_at', 'fulfilled_at']);
        });
    }
};
