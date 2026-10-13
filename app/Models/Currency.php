<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * An extra currency shoppers can browse prices in. `exchange_rate` is units of
 * this currency per 1 unit of the base currency (Settings → Localization).
 * Display only: orders are charged and recorded in the base currency.
 */
class Currency extends Model
{
    private const CACHE_KEY = 'currencies.active';

    /** Container key for the per-request copy (see activeList()). */
    private const MEMO_KEY = 'currencies.active.memo';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'symbol_position',
        'exchange_rate',
        'decimals',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'decimals' => 'integer',
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * Active currencies in display order, as plain arrays — cached, since the
     * storefront header reads them on every page.
     *
     * @return array<string, array{code: string, name: string, symbol: string, position: string, rate: float, decimals: int}>
     */
    public static function activeList(): array
    {
        // dprice() runs once per product card and the cache store is the
        // database, so keep a per-request copy in the container.
        if (app()->bound(self::MEMO_KEY)) {
            return app(self::MEMO_KEY);
        }

        $list = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->where('status', true)
            ->where('exchange_rate', '>', 0)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (self $c) => [$c->code => $c->toDisplay()])
            ->all());

        app()->instance(self::MEMO_KEY, $list);

        return $list;
    }

    public static function flushCache(): void
    {
        app()->forgetInstance(self::MEMO_KEY);
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{code: string, name: string, symbol: string, position: string, rate: float, decimals: int}
     */
    public function toDisplay(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'position' => $this->symbol_position === 'after' ? 'after' : 'before',
            'rate' => (float) $this->exchange_rate,
            'decimals' => (int) $this->decimals,
        ];
    }
}
