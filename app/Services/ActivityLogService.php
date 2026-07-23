<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ActivityLogService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->query($filters)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        return [
            'total' => OrderEvent::count(),
            'today' => OrderEvent::whereDate('created_at', today())->count(),
            'manual' => OrderEvent::whereNotNull('user_id')->count(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function types(): array
    {
        return OrderEvent::query()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type', 'type')
            ->map(fn (string $type): string => str($type)->headline()->toString())
            ->all();
    }

    public function actors(): Collection
    {
        return User::query()
            ->whereIn('id', OrderEvent::query()->whereNotNull('user_id')->select('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  resource  $handle
     */
    public function writeCsv(array $filters, $handle): void
    {
        fputcsv($handle, [
            'ID',
            'Date',
            'Type',
            'Title',
            'Body',
            'Order',
            'Actor',
            'Actor Email',
        ]);

        $this->query($filters)
            ->oldest()
            ->chunkById(500, function ($events) use ($handle): void {
                foreach ($events as $event) {
                    fputcsv($handle, [
                        $event->id,
                        optional($event->created_at)->toDateTimeString(),
                        $event->type,
                        $event->title,
                        $event->body,
                        optional($event->order)->order_number ?? $event->order_id,
                        optional($event->actor)->name,
                        optional($event->actor)->email,
                    ]);
                }
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        return OrderEvent::query()
            ->with(['order:id,order_number,status,grand_total', 'actor:id,name,email'])
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['user_id'] ?? null, fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['order_id'] ?? null, fn (Builder $query, int $orderId) => $query->where('order_id', $orderId))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $term = trim($search);

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('title', 'like', "%{$term}%")
                        ->orWhere('body', 'like', "%{$term}%")
                        ->orWhereHas('order', fn (Builder $query) => $query->where('order_number', 'like', "%{$term}%"))
                        ->orWhereHas('actor', function (Builder $query) use ($term): void {
                            $query->where('name', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%");
                        });
                });
            });
    }
}
