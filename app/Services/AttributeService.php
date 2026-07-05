<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttributeService
{
    /**
     * Paginated, filtered attribute list for the admin index.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = trim($filters['search'] ?? '');

        return Attribute::query()
            ->withCount('values')
            ->with('values:id,attribute_id,value,color_hex')
            ->search($search)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create an attribute with its values in a single transaction.
     *
     * @param  array<string, mixed>  $data  Validated request data.
     */
    public function create(array $data): Attribute
    {
        return DB::transaction(function () use ($data) {
            $attribute = Attribute::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'status' => $data['status'],
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $this->syncValues($attribute, $data['values'] ?? []);

            return $attribute;
        });
    }

    /**
     * Update an attribute and its values in a single transaction.
     *
     * @param  array<string, mixed>  $data  Validated request data.
     */
    public function update(Attribute $attribute, array $data): Attribute
    {
        return DB::transaction(function () use ($attribute, $data) {
            $attribute->update([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name'], $attribute->id),
                'status' => $data['status'],
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $this->syncValues($attribute, $data['values'] ?? []);

            return $attribute;
        });
    }

    /**
     * Create/update/delete an attribute's values from the submitted rows.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncValues(Attribute $attribute, array $rows): void
    {
        $order = 0;

        // Remove deleted values first so their slugs free up for reuse this save.
        $submittedIds = collect($rows)->pluck('id')->filter()->map('intval')->all();
        $attribute->values()->whereNotIn('id', $submittedIds ?: [0])->delete();

        foreach ($rows as $row) {
            $label = trim((string) ($row['value'] ?? ''));
            if ($label === '') {
                continue;
            }

            $existing = ! empty($row['id'])
                ? $attribute->values()->whereKey($row['id'])->first()
                : null;

            $payload = [
                'value' => $label,
                'slug' => $this->uniqueValueSlug($attribute, $label, $existing?->id),
                'color_hex' => ($row['color_hex'] ?? '') ?: null,
                'sort_order' => (int) ($row['sort_order'] ?? $order++),
                'status' => true,
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                $attribute->values()->create($payload);
            }
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'attribute';
        $slug = $base;
        $suffix = 2;

        while (
            Attribute::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function uniqueValueSlug(Attribute $attribute, string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'value';
        $slug = $base;
        $suffix = 2;

        while (
            $attribute->values()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
