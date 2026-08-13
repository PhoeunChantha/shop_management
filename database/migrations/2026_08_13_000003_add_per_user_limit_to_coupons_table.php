<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-customer redemption cap. `usage_limit` is the global cap; this is how
     * many times a single signed-in customer may redeem the coupon (null =
     * unlimited per customer, still bounded by the global cap). Enforced in
     * CheckoutService by counting the customer's prior orders on this coupon.
     */
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->unsignedInteger('per_user_limit')->nullable()->after('usage_limit');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->dropColumn('per_user_limit');
        });
    }
};
