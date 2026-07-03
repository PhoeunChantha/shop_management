<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('sub_category_id')->constrained('brands')->nullOnDelete();
            $table->string('short_description')->nullable()->after('description');
            $table->string('thumbnail')->nullable()->after('short_description');
            $table->decimal('cost_price', 10, 2)->nullable()->after('price');
            $table->decimal('weight', 8, 2)->nullable()->after('discount_amount');
            $table->boolean('is_featured')->default(false)->after('status');
            $table->boolean('is_new')->default(false)->after('is_featured');
            $table->boolean('is_best_seller')->default(false)->after('is_new');
            $table->boolean('is_on_sale')->default(false)->after('is_best_seller');
            $table->integer('sort_order')->default(0)->after('is_on_sale');
            $table->string('seo_title')->nullable()->after('sort_order');
            $table->string('seo_description', 500)->nullable()->after('seo_title');

            $table->index(['status', 'is_featured']);
            $table->index('brand_id');
        });

        // Expand status to the publishing lifecycle (MySQL). Existing
        // active/inactive values are preserved.
        DB::statement("ALTER TABLE products MODIFY status VARCHAR(20) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropIndex(['status', 'is_featured']);
            $table->dropColumn([
                'short_description', 'thumbnail', 'cost_price', 'weight',
                'is_featured', 'is_new', 'is_best_seller', 'is_on_sale',
                'sort_order', 'seo_title', 'seo_description',
            ]);
        });

        DB::statement("ALTER TABLE products MODIFY status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
    }
};
