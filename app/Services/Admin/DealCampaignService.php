<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DealCampaign;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DealCampaignService
{
    public function __construct(
        private readonly ImageFieldService $images,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return DealCampaign::query()
            ->withCount('products')
            ->search($filters['search'] ?? null)
            ->type($filters['type'] ?? null)
            ->when(($filters['lifecycle'] ?? null) === 'active', fn ($query) => $query
                ->where('status', true)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now())))
            ->when(($filters['lifecycle'] ?? null) === 'scheduled', fn ($query) => $query
                ->where('status', true)
                ->where('starts_at', '>', now()))
            ->when(($filters['lifecycle'] ?? null) === 'expired', fn ($query) => $query
                ->where('status', true)
                ->whereNotNull('ends_at')
                ->where('ends_at', '<', now()))
            ->when(($filters['lifecycle'] ?? null) === 'disabled', fn ($query) => $query->where('status', false))
            ->orderByDesc('priority')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, Request $request): DealCampaign
    {
        return DB::transaction(function () use ($data, $request): DealCampaign {
            $campaign = DealCampaign::create($this->payload($data));
            $this->syncImage($campaign, $request, false);
            $campaign->products()->sync($data['products'] ?? []);

            return $campaign->fresh('products');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DealCampaign $campaign, array $data, Request $request): DealCampaign
    {
        return DB::transaction(function () use ($campaign, $data, $request): DealCampaign {
            $campaign->update($this->payload($data, $campaign->id));
            $this->syncImage($campaign, $request, true);
            $campaign->products()->sync($data['products'] ?? []);

            return $campaign->fresh('products');
        });
    }

    public function delete(DealCampaign $campaign): void
    {
        $this->images->delete($campaign->image, 'deals');
        $campaign->delete();
    }

    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        return [
            'total' => DealCampaign::count(),
            'active' => DealCampaign::query()
                ->where('status', true)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->count(),
            'scheduled' => DealCampaign::query()->where('status', true)->where('starts_at', '>', now())->count(),
            'expired' => DealCampaign::query()->where('status', true)->whereNotNull('ends_at')->where('ends_at', '<', now())->count(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, thumb: ?string, price: string}>
     */
    public function productOptions(): array
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'thumbnail', 'price'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'thumb' => $product->thumbnail_url,
                'price' => number_format((float) $product->price, 2),
            ])
            ->all();
    }

    public function types(): array
    {
        return DealCampaign::TYPES;
    }

    public function productsForShow(DealCampaign $campaign): EloquentCollection
    {
        return $campaign->products()
            ->with(['category:id,name'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, ?int $ignoreId = null): array
    {
        $data['slug'] = $this->uniqueSlug($data['title'], $ignoreId);

        if (blank($data['discount_type'] ?? null)) {
            $data['discount_type'] = null;
            $data['discount_value'] = 0;
        }

        return collect($data)
            ->except(['image', 'image_media', 'products'])
            ->all();
    }

    private function syncImage(DealCampaign $campaign, Request $request, bool $replace): void
    {
        if ($request->hasFile('image')) {
            $replace
                ? $this->images->replaceUploaded($campaign, $request->file('image'), 'deals')
                : $this->images->attachUploaded($campaign, $request->file('image'), 'deals');

            return;
        }

        $filename = trim((string) $request->input('image_media', ''));
        if ($filename === '') {
            return;
        }

        $exists = DB::table('media_assets')
            ->where('folder', 'deals')
            ->where('filename', $filename)
            ->exists();

        if ($exists) {
            $this->images->attachSelected($campaign, $filename);
        }
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'deal';
        $slug = $base;
        $suffix = 2;

        while (
            DealCampaign::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
