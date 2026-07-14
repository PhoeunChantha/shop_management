<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('image');
            $table->string('kicker')->nullable();       // small eyebrow text above the title
            $table->string('title');
            $table->string('subtitle')->nullable();      // supporting copy
            $table->string('cta_text')->nullable();      // button label
            $table->string('cta_link')->nullable();      // button URL / path
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
