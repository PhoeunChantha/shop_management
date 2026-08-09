<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value'];

    private const CACHE_KEY = 'settings.map';

    protected static function booted(): void
    {
        // Any write/delete invalidates the cached map.
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * Get a setting value by key. Backed by the cached map so a request that
     * reads many settings hits the database at most once.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::map()[$key] ?? $default;
    }

    /**
     * Create or update a setting.
     */
    public static function set(string $key, ?string $value, string $group = 'general'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group],
        );

        static::flushCache();
    }

    /**
     * All settings as a key => value map (cached until a setting changes).
     *
     * @return array<string, mixed>
     */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => static::query()->pluck('value', 'key')->toArray());
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
