<?php

namespace App\Services;

use App\Models\AdminSavedView;
use Illuminate\Support\Collection;

class AdminSavedViewService
{
    /**
     * @return Collection<int, AdminSavedView>
     */
    public function forScope(string $scope, ?int $userId): Collection
    {
        return AdminSavedView::query()
            ->visibleTo($userId)
            ->where('scope', $scope)
            ->orderByDesc('is_global')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<string, Collection<int, AdminSavedView>>
     */
    public function grouped(?int $userId): Collection
    {
        return AdminSavedView::query()
            ->with('user:id,name,email')
            ->visibleTo($userId)
            ->orderBy('scope')
            ->orderByDesc('is_global')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('scope');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $userId): AdminSavedView
    {
        $query = json_decode((string) ($data['query_json'] ?? '{}'), true);
        $query = is_array($query) ? $this->cleanQuery($query) : [];
        $isGlobal = (bool) ($data['is_global'] ?? false);

        return AdminSavedView::updateOrCreate(
            [
                'user_id' => $isGlobal ? null : $userId,
                'scope' => $data['scope'],
                'name' => $data['name'],
            ],
            [
                'route_name' => $data['route_name'],
                'query' => $query,
                'icon' => $data['icon'] ?? $this->iconForScope($data['scope']),
                'color' => $data['color'] ?? '#0f766e',
                'is_global' => $isGlobal,
                'sort_order' => (int) ($data['sort_order'] ?? 50),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function cleanQuery(array $query): array
    {
        unset($query['page'], $query['_token'], $query['_method']);

        return collect($query)
            ->filter(fn ($value): bool => ! ($value === null || $value === '' || $value === []))
            ->all();
    }

    private function iconForScope(string $scope): string
    {
        return match ($scope) {
            'products' => 'fa-box-open',
            'orders' => 'fa-receipt',
            'customers' => 'fa-user-group',
            'returns' => 'fa-rotate-left',
            'media' => 'fa-photo-film',
            default => 'fa-filter',
        };
    }
}
