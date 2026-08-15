<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Timestamp of the one-time recovery email so the scheduled reminder command
     * never emails the same abandoned cart twice.
     */
    public function up(): void
    {
        Schema::table('abandoned_carts', function (Blueprint $table): void {
            $table->timestamp('reminder_sent_at')->nullable()->after('contacted_at');
        });
    }

    public function down(): void
    {
        Schema::table('abandoned_carts', function (Blueprint $table): void {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
