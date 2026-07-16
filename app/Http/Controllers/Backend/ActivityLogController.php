<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $perPage = (int) ($filters['per_page'] ?? 25);

        return view('admin.activity.index', [
            'events' => $this->query($filters)
                ->latest()
                ->paginate($perPage)
                ->withQueryString(),
            'stats' => $this->stats(),
            'types' => $this->types(),
            'actors' => User::query()
                ->whereIn('id', OrderEvent::query()->whereNotNull('user_id')->select('user_id'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'perPage' => $perPage,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $filename = 'activity-log-'.now()->format('Y-m-d-His').'.csv';

        return ResponseFactory::streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');

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

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
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

    /**
     * @return array<string, int>
     */
    private function stats(): array
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
    private function types(): array
    {
        return OrderEvent::query()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type', 'type')
            ->map(fn (string $type): string => str($type)->headline()->toString())
            ->all();
    }
}
