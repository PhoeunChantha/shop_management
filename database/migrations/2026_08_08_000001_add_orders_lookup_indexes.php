<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The account order lookup filters on customer_email (OR user_id) and the
     * lists order by placed_at — neither column was indexed. Add both.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->index('customer_email');
            $table->index('placed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['customer_email']);
            $table->dropIndex(['placed_at']);
        });
    }
};
