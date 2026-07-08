<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // 'variable' keeps every existing product (which already has variants) valid.
            $table->enum('product_type', ['single', 'variable'])->default('variable')->after('brand_id');

            // Single products have no variant rows — they carry their own sku/stock.
            $table->string('sku')->nullable()->unique()->after('thumbnail');
            $table->integer('stock')->default(0)->after('sku');
            $table->integer('low_stock_alert')->default(0)->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('product_type');
            $table->dropUnique(['sku']);
            $table->dropColumn(['sku', 'stock', 'low_stock_alert']);
        });
    }
};
