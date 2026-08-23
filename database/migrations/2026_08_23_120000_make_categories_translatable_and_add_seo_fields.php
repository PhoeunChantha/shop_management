<?php

use App\Services\Admin\SettingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Categories become multilingual (spatie/laravel-translatable, like products):
 * name / description / seo_title / seo_description / seo_keywords are stored as
 * per-language JSON. Existing plain values are wrapped under the primary language.
 * Also adds seo_keywords + seo_image.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private array $translatable = ['name', 'description', 'seo_title', 'seo_description'];

    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            // Widen to text first so JSON payloads never hit the 255 limit (MySQL),
            // and so existing non-JSON strings are never rejected by a JSON column.
            $table->text('name')->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->text('seo_title')->nullable()->change();
            $table->text('seo_description')->nullable()->change();
            $table->text('seo_keywords')->nullable()->after('seo_description');
            $table->string('seo_image')->nullable()->after('seo_keywords');
        });

        $primary = $this->primaryLanguage();

        DB::table('categories')->orderBy('id')->chunkById(200, function ($rows) use ($primary): void {
            foreach ($rows as $row) {
                $update = [];

                foreach ($this->translatable as $column) {
                    $value = $row->{$column};

                    if ($value === null || $value === '') {
                        continue;
                    }

                    $decoded = json_decode((string) $value, true);
                    if (is_array($decoded)) {
                        continue; // already translated JSON
                    }

                    $update[$column] = json_encode([$primary => (string) $value], JSON_UNESCAPED_UNICODE);
                }

                if ($update !== []) {
                    DB::table('categories')->where('id', $row->id)->update($update);
                }
            }
        });
    }

    public function down(): void
    {
        $primary = $this->primaryLanguage();

        DB::table('categories')->orderBy('id')->chunkById(200, function ($rows) use ($primary): void {
            foreach ($rows as $row) {
                $update = [];

                foreach ($this->translatable as $column) {
                    $decoded = json_decode((string) $row->{$column}, true);
                    if (is_array($decoded)) {
                        $update[$column] = $decoded[$primary] ?? (reset($decoded) ?: null);
                    }
                }

                if ($update !== []) {
                    DB::table('categories')->where('id', $row->id)->update($update);
                }
            }
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn(['seo_keywords', 'seo_image']);
            $table->string('name')->change();
            $table->text('description')->nullable()->change();
            $table->string('seo_title')->nullable()->change();
            $table->string('seo_description', 500)->nullable()->change();
        });
    }

    private function primaryLanguage(): string
    {
        try {
            return app(SettingService::class)->primaryLanguage();
        } catch (Throwable) {
            return 'en';
        }
    }
};
