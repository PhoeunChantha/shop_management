<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Support\ProductPorter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet 2 of the import template — the valid values for the name-mapped columns
 * (categories, brands) and the enum columns, laid out side by side.
 */
final class ProductReferenceSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  array<int, string>  $categories
     * @param  array<int, string>  $brands
     */
    public function __construct(
        private readonly array $categories,
        private readonly array $brands,
    ) {}

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        $columns = [
            $this->categories,
            $this->brands,
            ProductPorter::PRODUCT_TYPES,
            ProductPorter::STATUSES,
            ProductPorter::DISCOUNT_TYPES,
        ];

        $rowCount = max(array_map('count', $columns));
        $rows = [];

        for ($i = 0; $i < $rowCount; $i++) {
            $rows[] = [
                $this->categories[$i] ?? '',
                $this->brands[$i] ?? '',
                ProductPorter::PRODUCT_TYPES[$i] ?? '',
                ProductPorter::STATUSES[$i] ?? '',
                ProductPorter::DISCOUNT_TYPES[$i] ?? '',
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Categories (valid)', 'Brands (valid)', 'product_type', 'status', 'discount_type'];
    }

    public function title(): string
    {
        return 'Reference';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return ['1' => ['font' => ['bold' => true]]];
    }
}
