<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes backing the report range scans. Every report filters orders by
     * (payment_status, placed_at); the other tables are filtered by a date
     * column that had no index. An index is only created when no existing
     * index already leads with the same column(s) — MySQL auto-indexes foreign
     * keys, SQLite does not.
     *
     * @return array<string, array<int, array<int, string>>>
     */
    private function indexes(): array
    {
        return [
            'orders' => [['payment_status', 'placed_at']],
            'order_details' => [['product_id']],
            'payments' => [['status', 'created_at']],
            'return_requests' => [['refunded_at']],
            'stock_movements' => [['created_at']],
            'purchase_orders' => [['ordered_at']],
            'users' => [['created_at']],
        ];
    }

    public function up(): void
    {
        // Reports date sales by placed_at alone; checkout always sets it, but
        // make sure no legacy row falls out of every date range.
        DB::table('orders')->whereNull('placed_at')->update(['placed_at' => DB::raw('created_at')]);

        foreach ($this->indexes() as $table => $sets) {
            foreach ($sets as $columns) {
                if ($this->covered($table, $columns)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $t) => $t->index($columns, $this->name($table, $columns)));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes() as $table => $sets) {
            foreach ($sets as $columns) {
                $name = $this->name($table, $columns);

                if (collect(Schema::getIndexes($table))->contains('name', $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }

    /** Whether an existing index already starts with exactly these columns. */
    private function covered(string $table, array $columns): bool
    {
        return collect(Schema::getIndexes($table))->contains(
            fn (array $index) => array_slice($index['columns'], 0, count($columns)) === $columns,
        );
    }

    private function name(string $table, array $columns): string
    {
        return 'rpt_'.$table.'_'.implode('_', $columns).'_index';
    }
};
