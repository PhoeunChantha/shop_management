<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stable identifier for built-in "system" pages (about/privacy/terms). The
     * storefront resolves these pages by `page_key`, not the editable slug, so an
     * admin renaming a page's title (which regenerates the slug) never breaks its
     * public URL. Custom pages leave `page_key` null.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->string('page_key')->nullable()->unique()->after('slug');
        });

        // Backfill existing installs: link the seeded about/privacy/terms rows to
        // their key, and upgrade any still-placeholder content to the real copy
        // (leaves genuinely edited content untouched).
        $data = require database_path('data/system-pages.php');
        $placeholders = [
            '<p>Welcome to our store. Tell your brand story here.</p>',
            '<p>Describe how customer data is collected and used.</p>',
            '<p>Outline the terms of using your store and buying products.</p>',
        ];

        foreach ($data as $key => $attributes) {
            $page = Page::where('slug', $key)->orWhere('page_key', $key)->first();

            if (! $page) {
                continue;
            }

            $page->page_key = $key;

            if (blank($page->content) || in_array(trim((string) $page->content), $placeholders, true)) {
                $page->content = $attributes['content'];
                $page->seo_title = $page->seo_title ?: $attributes['seo_title'];
                $page->seo_description = $page->seo_description ?: $attributes['seo_description'];
            }

            $page->save();
        }
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropUnique(['page_key']);
            $table->dropColumn('page_key');
        });
    }
};
