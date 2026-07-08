<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Carry the existing hardcoded Size/Color data into the generic attribute system.
 *
 *  sizes            -> attribute "Size"  + its attribute_values
 *  colors           -> attribute "Color" + its attribute_values (color_hex preserved)
 *  product_variants -> product_variant_value pivot rows (size_id + color_id mapped)
 *
 * Non-destructive: the sizes/colors tables and product_variants.size_id/color_id are
 * left untouched so the current product form keeps working until Phase 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('attributes')->where('slug', 'size')->exists()) {
            return; // already seeded
        }

        $now = now();

        $sizeAttrId = DB::table('attributes')->insertGetId([
            'name' => 'Size', 'slug' => 'size', 'status' => true, 'sort_order' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $colorAttrId = DB::table('attributes')->insertGetId([
            'name' => 'Color', 'slug' => 'color', 'status' => true, 'sort_order' => 2,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        // sizes -> attribute_values (keyed by old size id => new value id)
        $sizeMap = [];
        if (Schema::hasTable('sizes')) {
            $usedSlugs = [];
            foreach (DB::table('sizes')->orderBy('sort_order')->get() as $size) {
                $slug = $this->uniqueSlug($size->code ?: $size->name, $usedSlugs);
                $sizeMap[$size->id] = DB::table('attribute_values')->insertGetId([
                    'attribute_id' => $sizeAttrId,
                    'value' => $size->name,
                    'slug' => $slug,
                    'color_hex' => null,
                    'status' => (bool) ($size->status ?? true),
                    'sort_order' => (int) ($size->sort_order ?? 0),
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // colors -> attribute_values
        $colorMap = [];
        if (Schema::hasTable('colors')) {
            $usedSlugs = [];
            foreach (DB::table('colors')->orderBy('sort_order')->get() as $color) {
                $slug = $this->uniqueSlug($color->name, $usedSlugs);
                $colorMap[$color->id] = DB::table('attribute_values')->insertGetId([
                    'attribute_id' => $colorAttrId,
                    'value' => $color->name,
                    'slug' => $slug,
                    'color_hex' => $color->hex_code ?? $color->code ?? null,
                    'status' => (bool) ($color->status ?? true),
                    'sort_order' => (int) ($color->sort_order ?? 0),
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // product_variants -> pivot rows
        if (Schema::hasTable('product_variants')) {
            $rows = [];
            foreach (DB::table('product_variants')->get() as $variant) {
                if (! empty($variant->size_id) && isset($sizeMap[$variant->size_id])) {
                    $rows[] = ['product_variant_id' => $variant->id, 'attribute_value_id' => $sizeMap[$variant->size_id], 'created_at' => $now, 'updated_at' => $now];
                }
                if (! empty($variant->color_id) && isset($colorMap[$variant->color_id])) {
                    $rows[] = ['product_variant_id' => $variant->id, 'attribute_value_id' => $colorMap[$variant->color_id], 'created_at' => $now, 'updated_at' => $now];
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('product_variant_value')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        DB::table('product_variant_value')->truncate();
        DB::table('attribute_values')->delete();
        DB::table('attributes')->whereIn('slug', ['size', 'color'])->delete();
    }

    private function uniqueSlug(string $source, array &$used): string
    {
        $base = Str::slug($source) ?: 'value';
        $slug = $base;
        $n = 2;
        while (in_array($slug, $used, true)) {
            $slug = $base.'-'.$n++;
        }
        $used[] = $slug;

        return $slug;
    }
};
