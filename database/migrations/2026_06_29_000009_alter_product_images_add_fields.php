<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('image');
            $table->boolean('is_primary')->default(false)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'is_primary']);
        });
    }
};
