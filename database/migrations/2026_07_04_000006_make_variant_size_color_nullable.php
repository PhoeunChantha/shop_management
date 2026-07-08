<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Variants are now defined by the product_variant_value pivot, so the legacy
 * size_id/color_id columns become optional. They are kept (nullable, FK dropped)
 * for one release as a safety net, and can be dropped in a later cleanup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign(['size_id']);
            $table->dropForeign(['color_id']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedBigInteger('size_id')->nullable()->change();
            $table->unsignedBigInteger('color_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreign('size_id')->references('id')->on('sizes')->cascadeOnDelete();
            $table->foreign('color_id')->references('id')->on('colors')->cascadeOnDelete();
        });
    }
};
