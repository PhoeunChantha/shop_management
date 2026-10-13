<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extra display currencies (the base currency stays in Settings → Localization).
 * Each row converts a base amount by `exchange_rate` for browsing only — orders
 * are still charged and recorded in the base currency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name');
            $table->string('symbol', 8);
            $table->string('symbol_position', 6)->default('before');
            $table->decimal('exchange_rate', 18, 6);
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->boolean('status')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });

        $this->importSecondaryCurrency();
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }

    /**
     * Carry the old single "second display currency" setting over as a row,
     * keeping whether it was switched on.
     */
    private function importSecondaryCurrency(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $setting = fn (string $key) => DB::table('settings')->where('key', $key)->value('value');

        $code = strtoupper(trim((string) $setting('currency_secondary_code')));
        $rate = (float) $setting('currency_secondary_rate');
        $base = strtoupper(trim((string) ($setting('currency_code') ?: 'USD')));

        if ($code === '' || $rate <= 0 || $code === $base) {
            return;
        }

        DB::table('currencies')->insert([
            'code' => $code,
            'name' => $code === 'KHR' ? 'Cambodian Riel' : $code,
            'symbol' => (string) ($setting('currency_secondary_symbol') ?: $code),
            'symbol_position' => $setting('currency_secondary_position') === 'before' ? 'before' : 'after',
            'exchange_rate' => $rate,
            'decimals' => (int) $setting('currency_secondary_decimals') === 2 ? 2 : 0,
            'status' => (string) $setting('currency_secondary_enabled') === '1',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
