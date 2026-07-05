<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            // size | color | custom — drives how the attribute's values are managed.
            $table->string('type')->default('custom')->after('name');
        });

        Schema::table('attribute_values', function (Blueprint $table) {
            // Polymorphic link to the master record (Size / Color) a value mirrors.
            // Null for custom (free-text) values. Uses the morph map ('size'/'color').
            $table->nullableMorphs('source'); // source_type + source_id + composite index
            $table->string('code')->nullable()->after('color_hex'); // e.g. XS, BLK (mirrored from source)
        });
    }

    public function down(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropMorphs('source');
            $table->dropColumn('code');
        });

        Schema::table('attributes', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
