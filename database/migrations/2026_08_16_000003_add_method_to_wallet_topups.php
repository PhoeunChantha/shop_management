<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_topups', function (Blueprint $table): void {
            $table->string('payment_method')->nullable()->after('tran_id');
            $table->string('method_type')->default('online')->after('payment_method');
            $table->string('admin_note')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->after('admin_note')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('approved_by');
            $table->index(['status', 'method_type']);
        });
    }

    public function down(): void
    {
        Schema::table('wallet_topups', function (Blueprint $table): void {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['status', 'method_type']);
            $table->dropColumn(['payment_method', 'method_type', 'admin_note', 'approved_by', 'reviewed_at']);
        });
    }
};
