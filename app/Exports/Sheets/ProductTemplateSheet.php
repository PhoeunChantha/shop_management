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
 * Sheet 1 of the import template — headings plus a single example row.
 */
final class ProductTemplateSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  array<int, string>  $languages
     */
    public function __construct(private readonly array $languages) {}

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [array_values(ProductPorter::exampleRow($this->languages))];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ProductPorter::headings($this->languages);
    }

    public function title(): string
    {
        return 'Products';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return ['1' => ['font' => ['bold' => true]]];
    }
}
