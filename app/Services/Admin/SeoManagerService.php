<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

final class SeoManagerService
{
    public const TYPES = [
        'product' => 'Products',
        'category' => 'Categories',
        'page' => 'Pages',
    ];

    public const ISSUES = [
        'missing_title' => 'Missing SEO title',
        'missing_description' => 'Missing description',
        'short_title' => 'Title too short',
        'long_title' => 'Title too long',
        'short_description' => 'Description too short',
        'long_description' => 'Description too long',
        'duplicate_slug' => 'Duplicate slug',
        'duplicate_title' => 'Duplicate title',
        'low_score' => 'Low score',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $items = $this->filteredRows($filters);
        $page = Paginator::resolveCurrentPage();

        return new Paginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()],
        );
    }

    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        $items = $this->rows();

        return [
            'total' => $items->count(),
            'healthy' => $items->where('score', '>=', 80)->count(),
            'missing' => $items->filter(fn (array $row) => in_array('missing_title', $row['issue_codes'], true) || in_array('missing_description', $row['issue_codes'], true))->count(),
            'duplicates' => $items->filter(fn (array $row) => in_array('duplicate_slug', $row['issue_codes'], true) || in_array('duplicate_title', $row['issue_codes'], true))->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  resource  $handle
     */
    public function writeCsv(array $filters, $handle): void
    {
        fputcsv($handle, ['Type', 'ID', 'Title', 'Slug', 'SEO Title', 'SEO Description', 'Score', 'Issues']);

        foreach ($this->filteredRows($filters) as $row) {
            fputcsv($handle, [
                $row['type_label'],
                $row['id'],
                $row['title'],
                $row['slug'],
                $row['seo_title'],
                $row['seo_description'],
                $row['score'],
                implode('; ', $row['issues']),
            ]);
        }
    }

    public function update(string $type, int $id, array $data): void
    {
        $locale = app()->getLocale();

        match ($type) {
            'product' => tap(Product::findOrFail($id), function (Product $product) use ($data, $locale): void {
                $product->setTranslation('seo_title', $locale, $data['seo_title'] ?? '');
                $product->setTranslation('seo_description', $locale, $data['seo_description'] ?? '');
                $product->save();
            }),
            'category' => Category::findOrFail($id)->update([
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
            ]),
            'page' => Page::findOrFail($id)->update([
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
            ]),
            default => abort(404),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function filteredRows(array $filters): Collection
    {
        $rows = $this->rows();

        if ($type = ($filters['type'] ?? null)) {
            $rows = $rows->where('type', $type);
        }

        if ($issue = ($filters['issue'] ?? null)) {
            $rows = $issue === 'low_score'
                ? $rows->filter(fn (array $row) => $row['score'] < 80)
                : $rows->filter(fn (array $row) => in_array($issue, $row['issue_codes'], true));
        }

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $needle = str($search)->lower()->value();
            $rows = $rows->filter(fn (array $row) => str($row['title'].' '.$row['slug'].' '.$row['seo_title'])->lower()->contains($needle));
        }

        return $rows->sortBy([['score', 'asc'], ['type', 'asc'], ['title', 'asc']])->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function rows(): Collection
    {
        $locale = app()->getLocale();
        $rows = collect();

        Product::query()
            ->with('category:id,name')
            ->get(['id', 'name', 'slug', 'seo_title', 'seo_description', 'status', 'category_id'])
            ->each(function (Product $product) use ($rows, $locale): void {
                $rows->push($this->row(
                    'product',
                    $product->id,
                    $product->getTranslation('name', $locale, false) ?: (string) $product->name,
                    $product->slug,
                    $product->getTranslation('seo_title', $locale, false) ?: '',
                    $product->getTranslation('seo_description', $locale, false) ?: '',
                    route('admin.products.edit', $product->id),
                    $product->status,
                ));
            });

        Category::query()
            ->get(['id', 'name', 'slug', 'seo_title', 'seo_description', 'status'])
            ->each(function (Category $category) use ($rows): void {
                $rows->push($this->row(
                    'category',
                    $category->id,
                    $category->name,
                    $category->slug,
                    $category->seo_title ?: '',
                    $category->seo_description ?: '',
                    route('admin.categories.edit', $category->id),
                    $category->status ? 'active' : 'inactive',
                ));
            });

        Page::query()
            ->get(['id', 'title', 'slug', 'seo_title', 'seo_description', 'status'])
            ->each(function (Page $page) use ($rows): void {
                $rows->push($this->row(
                    'page',
                    $page->id,
                    $page->title,
                    $page->slug,
                    $page->seo_title ?: '',
                    $page->seo_description ?: '',
                    route('admin.pages.edit', $page->id),
                    $page->status ? 'active' : 'inactive',
                ));
            });

        return $this->withDuplicateIssues($rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(string $type, int $id, string $title, string $slug, string $seoTitle, string $seoDescription, string $editUrl, string $status): array
    {
        [$score, $codes, $issues] = $this->score($seoTitle, $seoDescription);

        return [
            'type' => $type,
            'type_label' => self::TYPES[$type],
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'seo_title' => $seoTitle,
            'seo_description' => $seoDescription,
            'score' => $score,
            'issue_codes' => $codes,
            'issues' => $issues,
            'edit_url' => $editUrl,
            'status' => $status,
        ];
    }

    /**
     * @return array{0: int, 1: array<int, string>, 2: array<int, string>}
     */
    private function score(string $seoTitle, string $seoDescription): array
    {
        $score = 100;
        $codes = [];
        $issues = [];
        $titleLength = mb_strlen(trim($seoTitle));
        $descriptionLength = mb_strlen(trim($seoDescription));

        $checks = [
            ['missing_title', $titleLength === 0, 35],
            ['short_title', $titleLength > 0 && $titleLength < 30, 10],
            ['long_title', $titleLength > 60, 10],
            ['missing_description', $descriptionLength === 0, 35],
            ['short_description', $descriptionLength > 0 && $descriptionLength < 80, 10],
            ['long_description', $descriptionLength > 160, 10],
        ];

        foreach ($checks as [$code, $failed, $penalty]) {
            if (! $failed) {
                continue;
            }

            $score -= $penalty;
            $codes[] = $code;
            $issues[] = self::ISSUES[$code];
        }

        return [max(0, $score), $codes, $issues];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function withDuplicateIssues(Collection $rows): Collection
    {
        $slugCounts = $rows->countBy(fn (array $row) => str($row['slug'])->lower()->value());
        $titleCounts = $rows->filter(fn (array $row) => filled($row['seo_title']))
            ->countBy(fn (array $row) => str($row['seo_title'])->lower()->value());

        return $rows->map(function (array $row) use ($slugCounts, $titleCounts): array {
            if (($slugCounts[str($row['slug'])->lower()->value()] ?? 0) > 1) {
                $row['score'] = max(0, $row['score'] - 15);
                $row['issue_codes'][] = 'duplicate_slug';
                $row['issues'][] = self::ISSUES['duplicate_slug'];
            }

            if (filled($row['seo_title']) && ($titleCounts[str($row['seo_title'])->lower()->value()] ?? 0) > 1) {
                $row['score'] = max(0, $row['score'] - 15);
                $row['issue_codes'][] = 'duplicate_title';
                $row['issues'][] = self::ISSUES['duplicate_title'];
            }

            if ($row['score'] < 80 && ! in_array('low_score', $row['issue_codes'], true)) {
                $row['issue_codes'][] = 'low_score';
                $row['issues'][] = self::ISSUES['low_score'];
            }

            return $row;
        });
    }
}
