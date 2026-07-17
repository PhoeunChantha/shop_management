<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('status');
        });

        Schema::create('customer_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->default('#0f766e');
            $table->timestamps();
        });

        Schema::create('customer_profile_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['customer_profile_id', 'customer_tag_id']);
        });

        $now = now();
        DB::table('customer_tags')->insert([
            ['name' => 'VIP', 'color' => '#b45309', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Wholesale', 'color' => '#0f766e', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Risk', 'color' => '#be123c', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Blocked', 'color' => '#475569', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profile_tag');
        Schema::dropIfExists('customer_tags');

        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
