<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // Keep references but never rely on them for display — snapshots below.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            // Line snapshot (survives product/variant edits or deletion).
            $table->string('name');
            $table->string('variant_label')->nullable();   // e.g. "Black / M"
            $table->string('sku')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2);               // unit price at purchase
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 10, 2);          // price × quantity

            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
