<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persist the exact chosen variant on the saved cart so checkout can price +
     * decrement the precise variant instead of fuzzy-matching size/colour labels.
     * Nullable — guest/legacy lines and single products carry no variant.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table): void {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('product_variant_id');
        });
    }
};
