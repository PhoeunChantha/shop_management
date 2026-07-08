<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Product;
use App\Services\ProductService;
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
            fn (string $lang) => $product->getTranslation('name', $lang, false),
            $this->languages,
        );

        return array_merge(
            [$product->sku],
            $names,
            [
                $product->category?->name,
                $product->subCategory?->name,
                $product->brand?->name,
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
                $product->getTranslation('short_description', $this->primaryLang, false),
                $product->getTranslation('description', $this->primaryLang, false),
                $product->getTranslation('seo_title', $this->primaryLang, false),
                $product->getTranslation('seo_description', $this->primaryLang, false),
            ],
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return ['1' => ['font' => ['bold' => true]]];
    }
}
