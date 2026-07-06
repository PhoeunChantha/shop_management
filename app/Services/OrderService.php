<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class OrderService
{
    /**
     * Paginated, filtered order list for the admin index.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Order::query()
            ->with('user:id,name,email')
            ->withCount('details')
            ->withSum('details', 'quantity')
            ->search($search)
            ->status($filters['status'] ?? null)
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Update fulfilment fields from the admin detail page.
     *
     * @param  array<string, mixed>  $data  Validated request data.
     */
    public function updateFulfilment(Order $order, array $data): Order
    {
        $status = OrderStatus::from($data['status']);

        $order->status = $status;
        $order->tracking_number = $data['tracking_number'] ?? null;
        $order->admin_note = $data['admin_note'] ?? null;

        // Stamp payment the first time the order reaches a paid state.
        if ($order->paid_at === null && ! in_array($status, [OrderStatus::Pending, OrderStatus::Cancelled], true)) {
            $order->paid_at = now();
        }

        $order->save();

        return $order;
    }
}
