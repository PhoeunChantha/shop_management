<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->unsignedBigInteger('original_size')->nullable()->after('size');
            $table->unsignedBigInteger('optimized_size')->nullable()->after('original_size');
            $table->string('thumbnail_filename')->nullable()->after('filename');
            $table->string('optimization_status', 40)->default('pending')->after('height');
            $table->string('optimization_notes')->nullable()->after('optimization_status');
        });
    }

    public function down(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropColumn([
                'original_size',
                'optimized_size',
                'thumbnail_filename',
                'optimization_status',
                'optimization_notes',
            ]);
        });
    }
};
