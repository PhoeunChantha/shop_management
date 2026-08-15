<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The storefront listing (ShopController@index) now filters/paginates in the
     * database. The default order is (status, sort_order) newest-first and the
     * price facet filters/sorts on `price`. Existing indexes cover
     * (status, is_featured) and brand_id/category_id; add the two composites the
     * new listing leans on. Named explicitly so they are easy to identify/drop.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index(['status', 'sort_order'], 'products_status_sort_order_index');
            $table->index(['status', 'price'], 'products_status_price_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_status_sort_order_index');
            $table->dropIndex('products_status_price_index');
        });
    }
};
