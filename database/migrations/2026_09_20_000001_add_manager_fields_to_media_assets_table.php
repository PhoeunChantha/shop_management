<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            // Where the file physically lives. "local" = public/uploads via
            // ImageManager (every pre-existing row); anything else names a
            // filesystem disk, e.g. "r2".
            $table->string('disk', 40)->default('local')->after('folder');
            // Object key on a remote disk ("media/products/foo.jpg"). Null for local rows.
            $table->string('object_key')->nullable()->after('thumbnail_filename');
            $table->string('thumbnail_object_key')->nullable()->after('object_key');

            // Editable metadata — alt text already exists.
            $table->string('title')->nullable()->after('alt_text');
            $table->json('tags')->nullable()->after('title');

            // Duplicate detection.
            $table->string('checksum', 64)->nullable()->after('tags');

            // Cached usage count so the grid does not re-scan every reference
            // table per asset on each page load.
            $table->unsignedInteger('usage_count')->default(0)->after('checksum');
            $table->timestamp('usage_synced_at')->nullable()->after('usage_count');

            $table->index('checksum');
            $table->index('usage_count');
        });
    }

    public function down(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropIndex(['checksum']);
            $table->dropIndex(['usage_count']);
            $table->dropColumn([
                'disk',
                'object_key',
                'thumbnail_object_key',
                'title',
                'tags',
                'checksum',
                'usage_count',
                'usage_synced_at',
            ]);
        });
    }
};
