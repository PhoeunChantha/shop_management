<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('url')->nullable();
            $table->string('audience_type'); // 'all' | 'segment'
            $table->string('audience_summary')->nullable(); // human-readable label, e.g. "Tag: VIP"
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('registered_recipient_count')->default(0);
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notification_campaigns');
    }
};
