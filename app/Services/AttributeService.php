<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\Color;
use App\Models\Size;
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
                'type' => $data['type'],
                'status' => $data['status'],
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $this->syncValues($attribute, $data);

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
                'type' => $data['type'],
                'status' => $data['status'],
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $this->syncValues($attribute->refresh(), $data);

            return $attribute;
        });
    }

    /**
     * Route value syncing based on the attribute type.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncValues(Attribute $attribute, array $data): void
    {
        match ($attribute->type) {
            AttributeType::Size => $this->syncLinked($attribute, 'size', Size::class, $data['size_ids'] ?? []),
            AttributeType::Color => $this->syncLinked($attribute, 'color', Color::class, $data['color_ids'] ?? []),
            default => $this->syncCustomValues($attribute, $data['values'] ?? []),
        };
    }

    /**
     * Custom (free-text) values: create/update/delete from the submitted rows.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncCustomValues(Attribute $attribute, array $rows): void
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
                'code' => null,
                'source_type' => null,
                'source_id' => null,
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

    /**
     * Linked values (Size / Color): mirror the selected master rows, matching by
     * source so existing attribute-value ids survive (product variant links intact).
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  array<int, mixed>  $ids
     */
    private function syncLinked(Attribute $attribute, string $morph, string $model, array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        // Drop values for master rows no longer selected + any leftover custom rows.
        $attribute->values()->where('source_type', $morph)->whereNotIn('source_id', $ids ?: [0])->delete();
        $attribute->values()->whereNull('source_type')->delete();

        $masters = $model::query()->whereIn('id', $ids)->get()->keyBy('id');
        $isColor = $morph === 'color';

        foreach ($ids as $id) {
            $master = $masters->get($id);
            if (! $master) {
                continue;
            }

            $existing = $attribute->values()->where('source_type', $morph)->where('source_id', $id)->first();

            $payload = [
                'value' => $master->name,
                'slug' => $this->uniqueValueSlug($attribute, $master->name, $existing?->id),
                'code' => $master->code,
                'color_hex' => $isColor ? $master->hex_code : null,
                'source_type' => $morph,
                'source_id' => $id,
                'sort_order' => (int) ($master->sort_order ?? 0),
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
