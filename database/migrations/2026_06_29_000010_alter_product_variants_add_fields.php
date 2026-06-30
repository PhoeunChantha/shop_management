<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('sku');
            $table->integer('low_stock_alert')->default(0)->after('stock');
            $table->decimal('cost_price', 10, 2)->nullable()->after('price');
            $table->decimal('weight', 8, 2)->nullable()->after('cost_price');
            $table->boolean('status')->default(true)->after('weight');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['barcode', 'low_stock_alert', 'cost_price', 'weight', 'status']);
        });
    }
};
