<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table): void {
            // Cost of the item at the moment of sale — snapshot so historical COGS
            // stays accurate even when the product's cost_price later changes.
            $table->decimal('unit_cost', 10, 2)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table): void {
            $table->dropColumn('unit_cost');
        });
    }
};
