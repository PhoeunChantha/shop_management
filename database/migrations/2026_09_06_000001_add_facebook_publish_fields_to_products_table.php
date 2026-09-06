<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('facebook_post_id')->nullable()->after('seo_description');
            $table->string('facebook_permalink_url')->nullable()->after('facebook_post_id');
            $table->timestamp('facebook_posted_at')->nullable()->after('facebook_permalink_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['facebook_post_id', 'facebook_permalink_url', 'facebook_posted_at']);
        });
    }
};
