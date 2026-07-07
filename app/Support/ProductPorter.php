<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Single source of truth for the product import/export columns so the export,
 * the downloadable template and the importer all stay in lock-step.
 *
 * Translatable `name` gets one column per active language (`name_en`, `name_km`,
 * …); the other fields are the single-product core. Images and variants are out
 * of scope for the flat spreadsheet — they stay in the product editor.
 */
final class ProductPorter
{
    /** Columns after the per-language name columns. */
    public const CORE = [
        'category',
        'sub_category',
        'brand',
        'product_type',
        'price',
        'cost_price',
        'discount_type',
        'discount_amount',
        'stock',
        'low_stock_alert',
        'weight',
        'status',
        'is_featured',
        'is_new',
        'is_best_seller',
        'is_on_sale',
        'sort_order',
        'short_description',
        'description',
        'seo_title',
        'seo_description',
    ];

    /**
     * Ordered header keys for the given active languages.
     *
     * @param  array<int, string>  $languages
     * @return array<int, string>
     */
    public static function headings(array $languages): array
    {
        $nameColumns = array_map(static fn (string $lang) => "name_{$lang}", $languages);

        return array_merge(['sku'], $nameColumns, self::CORE);
    }

    /**
     * A single example row keyed by heading, for the template.
     *
     * @param  array<int, string>  $languages
     * @return array<string, string>
     */
    public static function exampleRow(array $languages): array
    {
        $row = ['sku' => 'TSHIRT-001'];

        foreach ($languages as $lang) {
            $row["name_{$lang}"] = $lang === 'km' ? 'អាវយឺតបុរាណ' : 'Classic T-Shirt';
        }

        return array_merge($row, [
            'category' => 'T-Shirts',
            'sub_category' => '',
            'brand' => 'Nike',
            'product_type' => 'single',
            'price' => '19.99',
            'cost_price' => '12.00',
            'discount_type' => 'percentage',
            'discount_amount' => '10',
            'stock' => '100',
            'low_stock_alert' => '10',
            'weight' => '0.25',
            'status' => 'active',
            'is_featured' => '1',
            'is_new' => '0',
            'is_best_seller' => '0',
            'is_on_sale' => '1',
            'sort_order' => '0',
            'short_description' => 'Soft cotton tee.',
            'description' => 'A comfortable everyday t-shirt.',
            'seo_title' => 'Classic T-Shirt',
            'seo_description' => 'Buy the classic cotton t-shirt online.',
        ]);
    }

    /** Allowed values for the enum-ish columns (used by the reference sheet + validation). */
    public const STATUSES = ['draft', 'active', 'inactive', 'archived'];

    public const DISCOUNT_TYPES = ['none', 'fixed', 'percentage'];

    public const PRODUCT_TYPES = ['single'];
}
