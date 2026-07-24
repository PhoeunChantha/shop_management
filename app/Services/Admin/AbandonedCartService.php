<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AbandonedCart;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AbandonedCartService
{
    public const AGE_FILTERS = [
        '1h' => '1+ hour',
        '24h' => '24+ hours',
        '3d' => '3+ days',
        '7d' => '7+ days',
    ];

    public const VALUE_FILTERS = [
        '50' => '$50+',
        '100' => '$100+',
        '250' => '$250+',
    ];

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->query($filters)
            ->withCount('items')
            ->latest('last_activity_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function stats(): array
    {
        return [
            'total' => AbandonedCart::count(),
            'new' => AbandonedCart::where('status', 'new')->count(),
            'value' => (float) AbandonedCart::whereIn('status', ['new', 'contacted'])->sum('subtotal'),
            'recovered' => AbandonedCart::where('status', 'recovered')->count(),
        ];
    }

    public function findForShow(AbandonedCart $cart): AbandonedCart
    {
        return $cart->load(['items.product', 'user:id,name,email']);
    }

    public function updateWorkflow(AbandonedCart $cart, array $data): AbandonedCart
    {
        $status = $data['status'] ?? $cart->status;
        $timestamps = [];

        if ($status === 'contacted' && $cart->contacted_at === null) {
            $timestamps['contacted_at'] = now();
        }

        if ($status === 'recovered' && $cart->recovered_at === null) {
            $timestamps['recovered_at'] = now();
        }

        if ($status === 'ignored' && $cart->ignored_at === null) {
            $timestamps['ignored_at'] = now();
        }

        $cart->update([
            'status' => $status,
            'admin_note' => $data['admin_note'] ?? $cart->admin_note,
            ...$timestamps,
        ]);

        return $cart;
    }

    public function delete(AbandonedCart $cart): void
    {
        $cart->delete();
    }

    public function writeCsv(array $filters, $handle): void
    {
        fputcsv($handle, ['ID', 'Customer', 'Email', 'Phone', 'Status', 'Items', 'Subtotal', 'Last Activity', 'Admin Note']);

        $this->query($filters)->withCount('items')->chunkById(500, function ($carts) use ($handle): void {
            foreach ($carts as $cart) {
                fputcsv($handle, [
                    $cart->id,
                    $cart->customer_name,
                    $cart->customer_email,
                    $cart->customer_phone,
                    $cart->statusLabel(),
                    $cart->items_count,
                    $cart->subtotal,
                    optional($cart->last_activity_at)->toDateTimeString(),
                    $cart->admin_note,
                ]);
            }
        });
    }

    private function query(array $filters)
    {
        return AbandonedCart::query()
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['value'] ?? null, fn ($query, $value) => $query->where('subtotal', '>=', (float) $value))
            ->when($filters['age'] ?? null, function ($query, string $age): void {
                $date = match ($age) {
                    '1h' => now()->subHour(),
                    '24h' => now()->subDay(),
                    '3d' => now()->subDays(3),
                    '7d' => now()->subDays(7),
                    default => null,
                };

                if ($date) {
                    $query->where('last_activity_at', '<=', $date);
                }
            });
    }
}
