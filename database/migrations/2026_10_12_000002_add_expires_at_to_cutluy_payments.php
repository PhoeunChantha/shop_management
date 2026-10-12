<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutluy_payments', function (Blueprint $table): void {
            // CutLuy QRs are short-lived (about five minutes).
            $table->timestamp('expires_at')->nullable()->after('qr_string');
        });
    }

    public function down(): void
    {
        Schema::table('cutluy_payments', function (Blueprint $table): void {
            $table->dropColumn('expires_at');
        });
    }
};
