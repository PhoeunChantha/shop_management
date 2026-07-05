<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Translatable text columns (spatie/laravel-translatable stores JSON here).
     *
     * @var array<int, string>
     */
    private array $fields = ['name', 'short_description', 'description', 'seo_title', 'seo_description'];

    public function up(): void
    {
        // Wrap existing plain values as { "en": "<value>" } before retyping to JSON.
        foreach ($this->fields as $field) {
            DB::statement("UPDATE products SET `{$field}` = JSON_OBJECT('en', `{$field}`) WHERE `{$field}` IS NOT NULL");
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE products MODIFY `name` JSON NOT NULL');
            DB::statement('ALTER TABLE products MODIFY `short_description` JSON NULL');
            DB::statement('ALTER TABLE products MODIFY `description` JSON NULL');
            DB::statement('ALTER TABLE products MODIFY `seo_title` JSON NULL');
            DB::statement('ALTER TABLE products MODIFY `seo_description` JSON NULL');
        }
    }

    public function down(): void
    {
        // Widen to TEXT so the JSON string is accepted, extract the English value,
        // then restore the original column types.
        foreach ($this->fields as $field) {
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE products MODIFY `{$field}` TEXT NULL");
            }

            DB::statement("UPDATE products SET `{$field}` = JSON_UNQUOTE(JSON_EXTRACT(`{$field}`, '$.en')) WHERE `{$field}` IS NOT NULL");
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE products MODIFY `name` VARCHAR(191) NOT NULL");
            DB::statement('ALTER TABLE products MODIFY `short_description` VARCHAR(191) NULL');
            DB::statement('ALTER TABLE products MODIFY `description` TEXT NULL');
            DB::statement('ALTER TABLE products MODIFY `seo_title` VARCHAR(191) NULL');
            DB::statement('ALTER TABLE products MODIFY `seo_description` VARCHAR(500) NULL');
        }
    }
};
