<?php

namespace App\Models;

use App\Services\Admin\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
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
        'parent_id',
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
        'parent_id' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Whether this category is referenced by any product or has sub-categories
     * (either blocks deletion).
     */
    public function isInUse(): bool
    {
        return $this->products()->exists() || $this->children()->exists();
    }

    /**
     * IDs of every category nested under this one, at any depth.
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $byParent = static::query()
            ->whereNotNull('parent_id')
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

        $ids = [];
        $queue = [$this->id];

        while ($queue !== []) {
            $parentId = array_shift($queue);

            foreach ($byParent->get($parentId, collect()) as $child) {
                $ids[] = (int) $child->id;
                $queue[] = (int) $child->id;
            }
        }

        return $ids;
    }

    /**
     * The top-most category this one sits under (itself when it is a root).
     */
    public function rootAncestor(): self
    {
        $node = $this;
        $seen = [$node->id];

        while ($node->parent_id && ($parent = $node->parent) && ! in_array($parent->id, $seen, true)) {
            $seen[] = $parent->id;
            $node = $parent;
        }

        return $node;
    }

    /**
     * Flattened tree for category pickers: depth-first in sort order, each
     * sub-category listed right under its parent with a dash per level
     * ("Main", "- Sub", "-- Sub-sub"); `depth` lets <x-select> indent and
     * shrink nested rows.
     * `$except` (and everything under it) is left out — used by the parent
     * picker so a category can never be made its own ancestor.
     *
     * @return array<int, array{id: int, label: string, depth: int}>
     */
    public static function treeOptions(?self $except = null): array
    {
        $excluded = $except ? [$except->id, ...$except->descendantIds()] : [];

        $groups = static::query()
            ->orderBy('sort_order')
            ->orderByName()
            ->get(['id', 'parent_id', 'name'])
            ->reject(fn (self $category): bool => in_array($category->id, $excluded, true))
            ->groupBy(fn (self $category): int => (int) ($category->parent_id ?? 0));

        $options = [];
        $walk = function (int $parentId, int $depth) use (&$walk, &$options, $groups): void {
            /** @var Collection<int, self> $siblings */
            $siblings = $groups->get($parentId, collect());

            foreach ($siblings as $category) {
                $options[] = [
                    'id' => (int) $category->id,
                    'label' => ($depth ? str_repeat('-', $depth).' ' : '').$category->name,
                    'depth' => $depth,
                ];
                $walk((int) $category->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $options;
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
