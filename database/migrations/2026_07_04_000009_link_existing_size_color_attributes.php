<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The first migration copied sizes/colors into a "Size"/"Color" attribute as plain
 * custom values. Now that attributes are typed and values can link back to their
 * master record, mark those two attributes as size/color-typed and point each value
 * at the matching sizes/colors row (mirroring its code).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->link('size', 'sizes');
        $this->link('color', 'colors');
    }

    public function down(): void
    {
        foreach (['size', 'color'] as $slug) {
            $attr = DB::table('attributes')->where('slug', $slug)->first();
            if ($attr) {
                DB::table('attributes')->where('id', $attr->id)->update(['type' => 'custom']);
                DB::table('attribute_values')->where('attribute_id', $attr->id)
                    ->update(['source_type' => null, 'source_id' => null, 'code' => null]);
            }
        }
    }

    private function link(string $type, string $table): void
    {
        $attr = DB::table('attributes')->where('slug', $type)->first();
        if (! $attr) {
            return;
        }

        DB::table('attributes')->where('id', $attr->id)->update(['type' => $type]);

        // name => [id, code] from the master table for matching by label.
        $master = DB::table($table)->get()->keyBy(fn ($r) => mb_strtolower(trim($r->name)));

        foreach (DB::table('attribute_values')->where('attribute_id', $attr->id)->get() as $value) {
            $row = $master->get(mb_strtolower(trim($value->value)));
            if (! $row) {
                continue;
            }

            DB::table('attribute_values')->where('id', $value->id)->update([
                'source_type' => $type,
                'source_id' => $row->id,
                'code' => $row->code ?? null,
            ]);
        }
    }
};
