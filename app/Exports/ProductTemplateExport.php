<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Sheets\ProductReferenceSheet;
use App\Exports\Sheets\ProductTemplateSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The downloadable import template: sheet 1 is the fillable template (headings +
 * one example row), sheet 2 lists the valid category/brand/enum values so the
 * user doesn't have to guess.
 */
final class ProductTemplateExport implements WithMultipleSheets
{
    /**
     * @param  array<int, string>  $languages
     * @param  array<int, string>  $categories
     * @param  array<int, string>  $brands
     */
    public function __construct(
        private readonly array $languages,
        private readonly array $categories,
        private readonly array $brands,
    ) {}

    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            new ProductTemplateSheet($this->languages),
            new ProductReferenceSheet($this->categories, $this->brands),
        ];
    }
}
