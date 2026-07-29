<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class NewsletterSubscriberService
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
            'total' => NewsletterSubscriber::count(),
            'today' => NewsletterSubscriber::whereDate('created_at', today())->count(),
            'this_month' => NewsletterSubscriber::whereBetween('created_at', [now()->startOfMonth(), now()])->count(),
        ];
    }

    /**
     * Stream subscribers to a CSV handle.
     *
     * @param  array<string, mixed>  $filters
     * @param  resource  $handle
     */
    public function writeCsv(array $filters, $handle): void
    {
        fputcsv($handle, ['Email', 'Subscribed at']);

        $this->query($filters)
            ->latest()
            ->chunk(500, function ($subscribers) use ($handle): void {
                foreach ($subscribers as $subscriber) {
                    fputcsv($handle, [
                        $subscriber->email,
                        ($subscriber->subscribed_at ?? $subscriber->created_at)?->format('Y-m-d H:i:s'),
                    ]);
                }
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<NewsletterSubscriber>
     */
    private function query(array $filters)
    {
        return NewsletterSubscriber::query()
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where('email', 'like', '%'.trim((string) $filters['search']).'%'),
            );
    }
}
