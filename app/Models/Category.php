<?php

namespace App\Models;

use App\Services\Admin\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasTranslations;

    /**
     * Per-language JSON columns (Settings → Languages). Reading `$category->name`
     * returns the current locale with fallback, exactly like Product.
     *
     * @var array<int, string>
     */
    public array $translatable = [
        'name',
        'description',
        'seo_title',
        'seo_description',
        'seo_keywords',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'icon',
        'sort_order',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'seo_image',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Whether this category is referenced by any product (blocks deletion).
     */
    public function isInUse(): bool
    {
        return $this->products()->exists();
    }

    /**
     * Filter categories by a search term against name (any language) / slug.
     * Skips filtering when blank.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(
            filled($term),
            fn (Builder $query) => $query->where(function (Builder $query) use ($term) {
                // The JSON payload contains every translation, so a plain LIKE
                // matches the term in any language.
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            })
        );
    }

    /**
     * Match a category by slug or by its name in any active language — used by
     * storefront URLs which may carry either.
     */
    public function scopeWhereSlugOrName(Builder $query, string $value): Builder
    {
        return $query->where(function (Builder $query) use ($value): void {
            $query->where('slug', $value);

            foreach (static::activeLanguageCodes() as $code) {
                $query->orWhere("name->{$code}", $value);
            }
        });
    }

    /**
     * Alphabetical order on the primary-language name.
     */
    public function scopeOrderByName(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('name->'.static::primaryLanguageCode(), $direction);
    }

    /**
     * @return array<int, string>
     */
    public static function activeLanguageCodes(): array
    {
        return app(SettingService::class)->languages();
    }

    public static function primaryLanguageCode(): string
    {
        return app(SettingService::class)->primaryLanguage();
    }
}
