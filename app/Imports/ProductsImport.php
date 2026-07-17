<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\SettingService;
use App\Support\ProductPorter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Imports single products from the spreadsheet template, upserting by SKU.
 *
 * Built for large files: category/brand lookups are pre-loaded into maps once
 * (no per-row queries) and rows are read in chunks. Invalid rows are collected
 * and reported instead of aborting the whole import.
 *
 * Only the first sheet is read — the template's "Reference" sheet is ignored
 * (Laravel Excel otherwise feeds every sheet to the importer).
 */
final class ProductsImport implements ToCollection, WithChunkReading, WithHeadingRow, WithMultipleSheets
{
    public int $created = 0;

    public int $updated = 0;

    /** @var array<int, array{row: int, messages: array<int, string>}> */
    public array $errors = [];

    public int $valid = 0;

    /** @var array<int, array<string, mixed>> */
    public array $previewRows = [];

    /** @var array<int, string> */
    private array $languages;

    private string $primaryLang;

    /** @var array<string, int> lowercased category name => id */
    private array $categoryMap;

    /** @var array<string, int> lowercased brand name => id */
    private array $brandMap;

    /** Spreadsheet row cursor (row 1 is the header). */
    private int $rowNumber = 1;

    private string $skuPrefix;

    public function __construct(SettingService $settings, private readonly bool $dryRun = false)
    {
        $this->languages = array_keys($settings->activeLanguages());
        $this->primaryLang = $settings->primaryLanguage();
        $this->skuPrefix = $settings->productSkuPrefix();

        $this->categoryMap = Category::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim((string) $name)) => (int) $id])
            ->all();

        $this->brandMap = Brand::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim((string) $name)) => (int) $id])
            ->all();
    }

    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Restrict the import to the first sheet (index 0), ignoring the Reference sheet.
     *
     * @return array<int, $this>
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->rowNumber++;

            $row = $row->toArray();

            // Skip fully blank rows without flagging them as errors.
            if (collect($row)->filter(fn ($v) => filled($v))->isEmpty()) {
                continue;
            }

            $this->processRow($row);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function processRow(array $row): void
    {
        $primaryName = trim((string) ($row["name_{$this->primaryLang}"] ?? ''));

        $data = [
            'name' => $primaryName,
            'category' => trim((string) ($row['category'] ?? '')),
            'sub_category' => trim((string) ($row['sub_category'] ?? '')),
            'brand' => trim((string) ($row['brand'] ?? '')),
            'price' => $row['price'] ?? null,
            'cost_price' => $this->blankToNull($row['cost_price'] ?? null),
            'discount_type' => $this->normalizeDiscountType($row['discount_type'] ?? null),
            'discount_amount' => $this->blankToNull($row['discount_amount'] ?? null),
            'stock' => $this->blankToNull($row['stock'] ?? null),
            'low_stock_alert' => $this->blankToNull($row['low_stock_alert'] ?? null),
            'weight' => $this->blankToNull($row['weight'] ?? null),
            'status' => trim((string) ($row['status'] ?? '')) ?: null,
            'sort_order' => $this->blankToNull($row['sort_order'] ?? null),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['required', 'string'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'low_stock_alert' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(ProductPorter::STATUSES)],
            'sort_order' => ['nullable', 'integer'],
        ], [], [
            'name' => "name_{$this->primaryLang}",
        ]);

        if ($validator->fails()) {
            $this->addError($validator->errors()->all());

            return;
        }

        // Resolve name-mapped relations.
        $categoryId = $this->categoryMap[mb_strtolower($data['category'])] ?? null;
        if ($categoryId === null) {
            $this->addError(["Unknown category “{$data['category']}”."]);

            return;
        }

        $subCategoryId = null;
        if ($data['sub_category'] !== '') {
            $subCategoryId = $this->categoryMap[mb_strtolower($data['sub_category'])] ?? null;
            if ($subCategoryId === null) {
                $this->addError(["Unknown sub-category “{$data['sub_category']}”."]);

                return;
            }
        }

        $brandId = null;
        if ($data['brand'] !== '') {
            $brandId = $this->brandMap[mb_strtolower($data['brand'])] ?? null;
            if ($brandId === null) {
                $this->addError(["Unknown brand “{$data['brand']}”."]);

                return;
            }
        }

        if ($this->dryRun) {
            $this->recordPreview($row, $data, $primaryName);

            return;
        }

        try {
            $this->upsert($row, $data, $primaryName, $categoryId, $subCategoryId, $brandId);
        } catch (\Throwable $e) {
            $this->addError(['Could not save row: '.$e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $data
     */
    private function recordPreview(array $row, array $data, string $primaryName): void
    {
        $this->valid++;

        if (count($this->previewRows) >= 12) {
            return;
        }

        $sku = trim((string) ($row['sku'] ?? ''));

        $this->previewRows[] = [
            'row' => $this->rowNumber,
            'action' => $sku !== '' && Product::where('sku', $sku)->exists() ? 'Update' : 'Create',
            'sku' => $sku !== '' ? $sku : 'Auto',
            'name' => $primaryName,
            'category' => $data['category'],
            'brand' => $data['brand'] !== '' ? $data['brand'] : 'No brand',
            'price' => $data['price'],
            'status' => $data['status'] ?? 'draft',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $data
     */
    private function upsert(array $row, array $data, string $primaryName, int $categoryId, ?int $subCategoryId, ?int $brandId): void
    {
        $sku = trim((string) ($row['sku'] ?? ''));

        $product = $sku !== '' ? Product::where('sku', $sku)->first() : null;
        $isUpdate = $product !== null;
        $product ??= new Product;

        $product->category_id = $categoryId;
        $product->sub_category_id = $subCategoryId;
        $product->brand_id = $brandId;
        $product->product_type = 'single';

        // name across every active language; the other translatable fields use the
        // primary language column only.
        foreach ($this->languages as $lang) {
            $value = trim((string) ($row["name_{$lang}"] ?? ''));
            if ($value !== '') {
                $product->setTranslation('name', $lang, $value);
            }
        }
        foreach (['short_description', 'description', 'seo_title', 'seo_description'] as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '') {
                $product->setTranslation($field, $this->primaryLang, $value);
            }
        }

        if (! $isUpdate || blank($product->slug)) {
            $product->slug = $this->uniqueSlug($primaryName, $product->id);
        }

        $product->sku = $sku !== '' ? $sku : ($product->sku ?: $this->generateSku());
        $product->price = $data['price'];
        $product->cost_price = $data['cost_price'];
        $product->discount_type = $data['discount_type'];
        $product->discount_amount = $data['discount_type'] ? ($data['discount_amount'] ?? 0) : 0;
        $product->stock = (int) ($data['stock'] ?? 0);
        $product->low_stock_alert = (int) ($data['low_stock_alert'] ?? 0);
        $product->weight = $data['weight'];
        $product->status = $data['status'] ?? 'draft';
        $product->is_featured = $this->boolFrom($row['is_featured'] ?? null);
        $product->is_new = $this->boolFrom($row['is_new'] ?? null);
        $product->is_best_seller = $this->boolFrom($row['is_best_seller'] ?? null);
        $product->is_on_sale = $this->boolFrom($row['is_on_sale'] ?? null);
        $product->sort_order = (int) ($data['sort_order'] ?? 0);

        $product->save();

        $isUpdate ? $this->updated++ : $this->created++;
    }

    /**
     * @param  array<int, string>  $messages
     */
    private function addError(array $messages): void
    {
        $this->errors[] = ['row' => $this->rowNumber, 'messages' => $messages];
    }

    private function normalizeDiscountType(mixed $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        return ($value === '' || $value === 'none') ? null : $value;
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function boolFrom(mixed $value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['1', 'yes', 'true', 'y'], true);
    }

    private function generateSku(): string
    {
        do {
            $sku = $this->skuPrefix.strtoupper(Str::random(8));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (
            Product::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
