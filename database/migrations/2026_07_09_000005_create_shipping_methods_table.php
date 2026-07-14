<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('type', 20)->default('flat');       // flat | free | free_over
            $table->decimal('rate', 10, 2)->default(0);         // charge (for flat / free_over below threshold)
            $table->decimal('free_over_amount', 10, 2)->nullable(); // subtotal at/above which shipping is free
            $table->string('delivery_time')->nullable();        // e.g. "2–4 business days"
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
