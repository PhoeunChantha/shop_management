<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Product;
use App\Services\Admin\ProductService;
use App\Support\ProductPorter;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Streams the filtered product list to a spreadsheet. FromQuery + chunking keeps
 * memory flat even for large catalogues.
 *
 * @implements WithMapping<Product>
 */
final class ProductsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $languages
     */
    public function __construct(
        private readonly ProductService $products,
        private readonly array $filters,
        private readonly array $languages,
        private readonly string $primaryLang,
    ) {}

    public function query(): Builder
    {
        return $this->products->filteredQuery($this->filters)
            ->with(['category', 'subCategory', 'brand'])
            ->withSum('variants', 'stock');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ProductPorter::headings($this->languages);
    }

    /**
     * @param  Product  $product
     * @return array<int, string|int|float|null>
     */
    public function map($product): array
    {
        $names = array_map(
            fn (string $lang) => self::escapeFormula($product->getTranslation('name', $lang, false)),
            $this->languages,
        );

        return array_merge(
            [self::escapeFormula($product->sku)],
            $names,
            [
                self::escapeFormula($product->category?->name),
                self::escapeFormula($product->subCategory?->name),
                self::escapeFormula($product->brand?->name),
                $product->product_type?->value,
                $product->price,
                $product->cost_price,
                $product->discount_type,
                $product->discount_amount,
                $product->total_stock,
                $product->low_stock_alert,
                $product->weight,
                $product->status,
                (int) $product->is_featured,
                (int) $product->is_new,
                (int) $product->is_best_seller,
                (int) $product->is_on_sale,
                $product->sort_order,
                self::escapeFormula($product->getTranslation('short_description', $this->primaryLang, false)),
                self::escapeFormula($product->getTranslation('description', $this->primaryLang, false)),
                self::escapeFormula($product->getTranslation('seo_title', $this->primaryLang, false)),
                self::escapeFormula($product->getTranslation('seo_description', $this->primaryLang, false)),
            ],
        );
    }

    /**
     * Neutralize spreadsheet formula/DDE injection: a cell value starting with
     * =, +, -, @, tab, or CR is prefixed with a leading apostrophe so Excel/Sheets
     * treats it as literal text instead of evaluating it as a formula.
     */
    private static function escapeFormula(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return ['1' => ['font' => ['bold' => true]]];
    }
}
