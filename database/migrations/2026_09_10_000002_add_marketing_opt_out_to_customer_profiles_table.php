<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->boolean('marketing_opt_out')->default(false)->after('status');
            $table->timestamp('marketing_opt_out_at')->nullable()->after('marketing_opt_out');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn(['marketing_opt_out', 'marketing_opt_out_at']);
        });
    }
};
