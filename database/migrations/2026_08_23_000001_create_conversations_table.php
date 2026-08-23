<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->string('last_message_preview', 160)->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->unsignedInteger('customer_unread')->default(0);
            $table->unsignedInteger('admin_unread')->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
