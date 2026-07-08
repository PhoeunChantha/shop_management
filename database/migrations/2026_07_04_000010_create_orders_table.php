<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();          // e.g. UT-2026-000123
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // null = guest
            $table->string('status')->default('pending');      // OrderStatus enum

            // Customer snapshot (kept even if the account changes/deletes).
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();

            // Shipping address snapshot.
            $table->string('shipping_address');
            $table->string('shipping_city')->nullable();
            $table->string('shipping_zip')->nullable();
            $table->string('shipping_country')->nullable();

            // Money (all snapshotted at placement).
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);

            // Discount / fulfilment / payment.
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code')->nullable();
            $table->string('shipping_method')->nullable();      // standard | express | pickup
            $table->string('tracking_number')->nullable();
            $table->string('payment_method')->nullable();       // card | cod | ...
            $table->timestamp('paid_at')->nullable();

            // Notes.
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();

            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
