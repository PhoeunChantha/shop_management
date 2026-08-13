<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reviews are meant to be one-per-customer-per-product (AccountService already
     * checks `hasReviewed` before inserting). Add a DB-level unique index as a
     * safety net against double-submit races. Guest reviews (null user_id) are
     * left unconstrained — SQLite/MySQL treat NULLs as distinct in a unique index.
     */
    public function up(): void
    {
        // Drop any pre-existing duplicates (keep the earliest) so the index applies.
        $duplicateIds = DB::table('reviews')
            ->whereNotNull('user_id')
            ->whereRaw('id NOT IN (SELECT MIN(id) FROM reviews WHERE user_id IS NOT NULL GROUP BY user_id, product_id)')
            ->pluck('id');

        if ($duplicateIds->isNotEmpty()) {
            DB::table('reviews')->whereIn('id', $duplicateIds)->delete();
        }

        Schema::table('reviews', function (Blueprint $table): void {
            $table->unique(['user_id', 'product_id'], 'reviews_user_product_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropUnique('reviews_user_product_unique');
        });
    }
};
